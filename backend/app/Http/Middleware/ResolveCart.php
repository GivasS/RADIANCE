<?php

namespace App\Http\Middleware;

use App\Services\CartService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCart
{
    public function __construct(private readonly CartService $carts) {}

    /**
     * Resolve o carrinho atual (do usuario logado ou anonimo via cookie)
     * e disponibiliza em $request->attributes->get('cart').
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum');

        if ($user) {
            $cart = $this->carts->findActiveForUser($user) ?? $this->carts->createForUser($user);
            $request->attributes->set('cart', $cart);

            return $next($request);
        }

        $token = $request->cookie(CartService::COOKIE_NAME);
        $cart = $token ? $this->carts->findActiveByToken($token) : null;
        $isNew = false;

        if (! $cart) {
            $cart = $this->carts->createAnonymous();
            $isNew = true;
        }

        $request->attributes->set('cart', $cart);

        /** @var Response $response */
        $response = $next($request);

        if ($isNew) {
            $response->headers->setCookie(cookie(
                name: CartService::COOKIE_NAME,
                value: $cart->session_token,
                minutes: CartService::COOKIE_DAYS * 24 * 60,
                httpOnly: true,
                secure: ! app()->environment('local'),
                sameSite: 'lax',
            ));
        }

        return $response;
    }
}
