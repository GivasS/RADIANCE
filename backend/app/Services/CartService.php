<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const COOKIE_NAME = 'cart_token';

    public const COOKIE_DAYS = 30;

    public function findActiveByToken(string $token): ?Cart
    {
        return Cart::where('session_token', $token)->where('status', 'active')->first();
    }

    public function findActiveForUser(User $user): ?Cart
    {
        return Cart::where('user_id', $user->id)->where('status', 'active')->first();
    }

    public function createAnonymous(): Cart
    {
        return Cart::create([
            'session_token' => (string) Str::uuid(),
            'status' => 'active',
            'expires_at' => now()->addDays(self::COOKIE_DAYS),
        ]);
    }

    public function createForUser(User $user): Cart
    {
        return Cart::create(['user_id' => $user->id, 'status' => 'active']);
    }

    /**
     * Estoque disponivel pra venda: estoque - reservas ativas e nao expiradas
     * (especificacoes.txt 1.2.16 / vw_estoque_disponivel).
     */
    public function availableStock(ProductVariant $variant): int
    {
        $reserved = DB::table('stock_reservations')
            ->where('variant_id', $variant->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->whereNull('deleted_at')
            ->sum('quantity');

        return max(0, $variant->stock_quantity - $reserved);
    }

    /**
     * Adiciona (ou soma quantidade, se ja existir) um item no carrinho.
     * Preco fica congelado no momento da adicao (especificacoes.txt 1.4.20).
     */
    public function addItem(Cart $cart, Product $product, ProductVariant $variant, int $quantity): CartItem
    {
        if (! $variant->active || $variant->product_id !== $product->id) {
            throw ValidationException::withMessages(['variant_id' => 'Variação inválida para este produto.']);
        }

        $available = $this->availableStock($variant);

        if ($available <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Produto sem estoque disponível.']);
        }

        $existing = CartItem::where('cart_id', $cart->id)->where('variant_id', $variant->id)->first();
        $desiredQuantity = ($existing?->quantity ?? 0) + $quantity;
        $finalQuantity = min($desiredQuantity, $available);

        $unitPrice = $variant->price_override ?? $product->promo_price ?? $product->price;

        if ($existing) {
            $existing->update(['quantity' => $finalQuantity]);

            return $existing;
        }

        return CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => $finalQuantity,
            'unit_price' => $unitPrice,
        ]);
    }

    public function updateItemQuantity(CartItem $item, int $quantity): CartItem
    {
        $available = $this->availableStock($item->variant);
        $item->update(['quantity' => min($quantity, max(1, $available))]);

        return $item;
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    /**
     * Remove itens marcados como indisponiveis (especificacoes.txt 1.2.11).
     */
    public function removeUnavailable(Cart $cart): int
    {
        $removed = 0;

        foreach ($cart->items()->with(['product', 'variant'])->get() as $item) {
            if ($this->situationFor($item) === 'indisponivel') {
                $item->delete();
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * 'indisponivel' | 'estoque_parcial' | 'ok' (especificacoes.txt 1.2.11-12).
     */
    public function situationFor(CartItem $item): string
    {
        $product = $item->product;
        $variant = $item->variant;

        if (! $product || $product->deleted_at || ! $product->active
            || ! $variant || $variant->deleted_at || ! $variant->active) {
            return 'indisponivel';
        }

        $available = $this->availableStock($variant);

        if ($available <= 0) {
            return 'indisponivel';
        }

        if ($available < $item->quantity) {
            return 'estoque_parcial';
        }

        return 'ok';
    }

    /**
     * Monta o payload do carrinho com situacao/preco atual por item
     * (especificacoes.txt 1.2.12 e 1.4.22 - preco congelado nunca sobe).
     */
    public function present(Cart $cart): array
    {
        $items = $cart->items()->with(['product.images', 'variant'])->get()->map(function (CartItem $item) {
            $situation = $this->situationFor($item);
            $available = $item->variant ? $this->availableStock($item->variant) : 0;

            $currentPrice = $item->variant?->price_override
                ?? $item->product?->promo_price
                ?? $item->product?->price;

            $effectivePrice = $currentPrice !== null
                ? min((float) $item->unit_price, (float) $currentPrice)
                : (float) $item->unit_price;

            if ($situation === 'estoque_parcial') {
                $item->quantity = $available;
                $item->save();
            }

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'product' => $item->product,
                'variant' => $item->variant,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'effective_unit_price' => $effectivePrice,
                'line_total' => round($effectivePrice * $item->quantity, 2),
                'situation' => $situation,
                'available_stock' => $available,
            ];
        });

        return [
            'id' => $cart->id,
            'items' => $items->values(),
            'subtotal' => round($items->sum('line_total'), 2),
            'has_unavailable' => $items->contains(fn ($i) => $i['situation'] === 'indisponivel'),
        ];
    }

    /**
     * Mescla o carrinho anonimo no carrinho do usuario ao logar
     * (especificacoes.txt 1.1.4-5).
     */
    public function mergeAnonymousIntoUser(Cart $anonymous, User $user): array
    {
        $userCart = $this->findActiveForUser($user) ?? $this->createForUser($user);
        $capped = [];

        DB::transaction(function () use ($anonymous, $userCart, &$capped) {
            foreach ($anonymous->items()->with('variant')->get() as $anonItem) {
                $userItem = CartItem::where('cart_id', $userCart->id)
                    ->where('variant_id', $anonItem->variant_id)
                    ->first();

                if ($userItem) {
                    $available = $anonItem->variant ? $this->availableStock($anonItem->variant) : 0;
                    $desired = $userItem->quantity + $anonItem->quantity;
                    $final = min($desired, max($available, $userItem->quantity));

                    if ($final < $desired) {
                        $capped[] = $userItem->variant_id;
                    }

                    // Regra: mantem o preco do carrinho do usuario logado (o mais antigo).
                    $userItem->update(['quantity' => $final]);
                } else {
                    CartItem::create([
                        'cart_id' => $userCart->id,
                        'product_id' => $anonItem->product_id,
                        'variant_id' => $anonItem->variant_id,
                        'quantity' => $anonItem->quantity,
                        'unit_price' => $anonItem->unit_price,
                    ]);
                }
            }

            $anonymous->items()->delete();
            $anonymous->update(['status' => 'converted']);
            $anonymous->delete();
        });

        return ['cart' => $userCart, 'capped_variant_ids' => $capped];
    }
}
