<?php

namespace App\Http\Requests\SalesConfirmation;

use Illuminate\Foundation\Http\FormRequest;

class DecideSalesConfirmationRequest extends FormRequest
{
    // NOTE: otorisasi Policy di controller agar cek status 409 bisa didahulukan dari 403.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => 'required|in:approve,reject',
            'note'     => 'nullable|string',
        ];
    }
}
