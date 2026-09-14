<?php

namespace App\Http\Requests\Auth;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class TeamLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'code' => ['required', 'string'],
        ];
    }

    /**
     * Verify the submitted PIN server-side and return the team, or throw a
     * validation error. Rate limited per team to slow down PIN guessing.
     */
    public function authenticate(): Team
    {
        $team = Team::query()->findOrFail($this->input('team_id'));

        $rateLimitKey = 'team-login:'.$team->id.'|'.$this->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            throw ValidationException::withMessages([
                'code' => 'Te veel pogingen. Probeer het over '.RateLimiter::availableIn($rateLimitKey).' seconden opnieuw.',
            ]);
        }

        if (! $team->active || ! Hash::check($this->input('code'), $team->code_hash)) {
            RateLimiter::hit($rateLimitKey, 60);

            throw ValidationException::withMessages([
                'code' => 'Ongeldige teamcode.',
            ]);
        }

        RateLimiter::clear($rateLimitKey);

        return $team;
    }
}
