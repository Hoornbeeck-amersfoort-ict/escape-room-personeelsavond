<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
        ];
    }

    /**
     * An empty datetime-local input submits as an empty string, not a
     * missing key. Left as-is, Eloquent's datetime cast would pass that
     * empty string to Carbon, which parses '' as "now" instead of null —
     * silently setting a start/end time nobody asked for. Normalize blank
     * values to null before they ever reach validation or the model.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'start_time' => $this->filled('start_time') ? $this->input('start_time') : null,
            'end_time' => $this->filled('end_time') ? $this->input('end_time') : null,
        ]);
    }
}
