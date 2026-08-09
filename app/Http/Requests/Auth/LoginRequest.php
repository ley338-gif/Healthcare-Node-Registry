<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:1024'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $email = mb_strtolower(trim((string) $this->validated('email')));
        $user = User::query()->where('email', $email)->first();
        if (! $user instanceof User || ! $user->is_active || ! Hash::check((string) $this->validated('password'), $user->password)) {
            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages([
                'email' => __('Die Anmeldedaten sind ungültig oder das Konto ist nicht verfügbar.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => Hash::make((string) $this->validated('password'))])->save();
        }

        return $user;
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => __('Zu viele Anmeldeversuche. Bitte später erneut versuchen.'),
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('email')).'|'.$this->ip()
        );
    }
}
