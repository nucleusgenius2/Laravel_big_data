<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class PostSearchRequest extends FormRequest
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
        return  [
            'page' => 'required|integer|min:1',
            'created_at_from' => 'string|date',
            'created_at_to' => 'string|date',
            'name' => 'string|min:1|max:50',
            'date_fixed' => 'string|in:day,week,month,year',
            'rating' => 'string|min:1|max:50',
            'authors' => 'integer',
        ];

    }
}
