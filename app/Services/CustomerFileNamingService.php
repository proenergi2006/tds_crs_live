<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerDocumentType;
use Illuminate\Support\Str;

/**
 * Konvensi naming/folder terpusat buat semua upload dokumen customer (LCR
 * photos maupun dokumen onboarding NIB/NPWP/dst), biar file di storage bisa
 * ditelusuri manual dari folder/nama-nya aja tanpa buka DB. Ini beda dari
 * `file_name` (kolom DB, ditampilkan di UI) yang tetap nama asli hasil upload user.
 *
 * Folder : customer_document/{customer_code}/{category}
 * File   : {customer_code}.{category}.{type-slug}[.{keterangan-slug}].{ext}
 */
class CustomerFileNamingService
{
    /** @return array{0: string, 1: string} [folder, fileName] */
    public static function build(Customer $customer, CustomerDocumentType $type, ?string $caption, string $extension): array
    {
        // Fallback ke id_customer ini murni jaga-jaga -- harusnya udah gak kepakai
        // lagi sejak CustomerCodeGenerator terpasang di SubmitCustomerOnboardingAction,
        // tapi lebih aman ada daripada exception kalau ada jalur lain yang lolos.
        $customerCode = $customer->customer_code ?: "id{$customer->id_customer}";
        $category = $type->category ?: 'general';
        $typeSlug = self::typeSlug($type->code);
        $captionSlug = $caption ? '.'.Str::slug($caption) : '';

        $folder = "customer_document/{$customerCode}/{$category}";
        $fileName = "{$customerCode}.{$category}.{$typeSlug}{$captionSlug}.{$extension}";

        return [$folder, $fileName];
    }

    private static function typeSlug(string $code): string
    {
        // Kode di DB pakai underscore & kadang ada prefix kategori (lcr_road_condition).
        // Prefix-nya dibuang di sini soalnya kategori udah jadi segmen folder+nama
        // sendiri, gak perlu diulang 2x.
        $slug = preg_replace('/^lcr_/', '', $code);

        return Str::slug($slug);
    }
}
