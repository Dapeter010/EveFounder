<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostJobRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:50',
            'description' => 'required|string|max:200',
            'address' => 'required|string|max:100',
            'longitude' => 'required|string|max:100',
            'latitude' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0',
            'datetime' => 'required|date|after:now|before: 1 years',

        ];
    }
}
