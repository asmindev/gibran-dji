<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOutgoingItemRequest extends FormRequest
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
            'outgoing_date' => 'required|date',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'recipient' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}
