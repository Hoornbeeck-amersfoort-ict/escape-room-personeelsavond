<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rooms', 'name')
                    ->where('game_id', $this->route('room')->game_id)
                    ->ignore($this->route('room')->id),
            ],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'max:5120'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['string'],
            'answer' => ['required', 'string', 'max:255'],
            'alternative_answers' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
