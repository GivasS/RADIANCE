<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\CartService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly CartService $carts) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'cpf' => $request->cpf,
            'phone' => $request->phone,
            'password_hash' => $request->password,
            'role' => 'customer',
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        $mergeInfo = $this->mergeAnonymousCartIfAny($request, $user);

        return response()->json(['user' => $user, 'cart_merge' => $mergeInfo], 201)
            ->withCookie(cookie()->forget(CartService::COOKIE_NAME));
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $key = $this->rateLimitKey($request);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => "Muitas tentativas. Tente novamente em {$seconds} segundos.",
            ]);
        }

        if (! Auth::attempt($request->only('email', 'password'), true)) {
            RateLimiter::hit($key, 900); // 15 minutos

            throw ValidationException::withMessages([
                'email' => 'Credenciais inválidas.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();
        $user->forceFill(['last_login_at' => now()])->save();

        $mergeInfo = $this->mergeAnonymousCartIfAny($request, $user);

        return response()->json(['user' => $user, 'cart_merge' => $mergeInfo])
            ->withCookie(cookie()->forget(CartService::COOKIE_NAME));
    }

    /**
     * Mescla o carrinho anonimo (cookie) no carrinho do usuario que acabou
     * de logar/se cadastrar (especificacoes.txt 1.1.4-5).
     */
    private function mergeAnonymousCartIfAny(Request $request, User $user): ?array
    {
        $token = $request->cookie(CartService::COOKIE_NAME);

        if (! $token) {
            return null;
        }

        $anonymousCart = $this->carts->findActiveByToken($token);

        if (! $anonymousCart || ! $anonymousCart->items()->exists()) {
            return null;
        }

        $result = $this->carts->mergeAnonymousIntoUser($anonymousCart, $user);

        return [
            'merged' => true,
            'capped_variant_ids' => $result['capped_variant_ids'],
        ];
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Sessão encerrada.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->whereNull('deleted_at')->first();

        // Nao revela se o e-mail existe ou nao (evita enumeracao de contas).
        if ($user) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $user->notify(new ResetPasswordNotification($token, $user->email));
        }

        return response()->json(['message' => 'Se o e-mail existir, enviaremos as instruções de redefinição.']);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (! $record || ! Hash::check($request->token, $record->token)) {
            throw ValidationException::withMessages(['token' => 'Token inválido.']);
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            throw ValidationException::withMessages(['token' => 'Token expirado.']);
        }

        $user = User::where('email', $request->email)->whereNull('deleted_at')->firstOrFail();
        $user->forceFill(['password_hash' => $request->password])->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Senha redefinida com sucesso.']);
    }

    private function rateLimitKey(Request $request): string
    {
        return Str::lower((string) $request->input('email')).'|'.$request->ip();
    }
}
