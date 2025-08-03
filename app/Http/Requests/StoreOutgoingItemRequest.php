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
        $outgoingItemId = $this->route('outgoing_item') ? $this->route('outgoing_item')->id : null;

        return [
            'transaction_id' => [
                'required',
                'string',
                'max:255',
                'unique:outgoing_items,transaction_id' . ($outgoingItemId ? ',' . $outgoingItemId : '')
            ],
            'outgoing_date' => 'required|date',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'transaction_id.required' => 'ID Transaksi wajib diisi.',
            'transaction_id.unique' => 'ID Transaksi sudah digunakan. Gunakan ID yang berbeda.',
            'transaction_id.max' => 'ID Transaksi maksimal 255 karakter.',
            'outgoing_date.required' => 'Tanggal keluar wajib diisi.',
            'outgoing_date.date' => 'Format tanggal tidak valid.',
            'item_id.required' => 'Barang wajib dipilih.',
            'item_id.exists' => 'Barang yang dipilih tidak valid.',
            'quantity.required' => 'Jumlah wajib diisi.',
            'quantity.integer' => 'Jumlah harus berupa angka.',
            'quantity.min' => 'Jumlah minimal 1.',
        ];
    }
}
