<?php

namespace App\Http\Requests\Finance;

use App\Enums\FinanceTransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinanceTransactionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(FinanceTransactionType::class)],
            'category' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:1'],
            'transaction_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'jenis transaksi',
            'category' => 'kategori',
            'amount' => 'jumlah',
            'transaction_date' => 'tanggal',
            'notes' => 'catatan',
        ];
    }
}
