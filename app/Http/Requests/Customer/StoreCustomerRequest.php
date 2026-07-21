<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'             => 'nullable|email|unique:customers,email',
            // id_provinsi/id_kabupaten dilonggarkan jadi nullable (Task 8.1, opsi a) —
            // tabel referensi lama provinsis/kabupatens (13/25 baris) tidak lengkap
            // dibanding data BPS (province_id/regency_id di bawah, 38/514 baris), jadi
            // memaksa required di sini akan menolak submit valid dari dropdown BPS baru.
            'id_provinsi'       => 'nullable|exists:provinsis,id_provinsi',
            'id_kabupaten'      => 'nullable|exists:kabupatens,id_kabupaten',
            // Kolom baru berbasis kode BPS (laravel-nusa-address-full-migration).
            'province_id'       => 'nullable|string|exists:provinces,id',
            'regency_id'        => 'nullable|string|exists:regencies,id',
            'district_id'       => 'nullable|string|exists:districts,id',
            'village_id'        => 'nullable|string|exists:villages,id',
            'postal_code'       => 'nullable|string|max:20',
            'telepon'           => 'nullable|string|max:30',
            'jenis_customer'    => 'nullable|string|max:50',
            'nama_perusahaan'   => 'nullable|string|max:255',
            'alamat_perusahaan' => 'nullable|string',
            'fax'               => 'nullable|string|max:30',
        ];
    }
}
