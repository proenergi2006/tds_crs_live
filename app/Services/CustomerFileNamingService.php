<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerDocumentType;
use Illuminate\Support\Str;

class CustomerFileNamingService
{
    public static function build(Customer $customer, CustomerDocumentType $type, ?string $caption, string $extension): array
    {
        // customer_code kosong itu normal disini -- baru keisi pas admin finance approve verifikasi
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
        $slug = preg_replace('/^lcr_/', '', $code);

        return Str::slug($slug);
    }

    public static function buildFreeForm(Customer $customer, string $label, string $extension): array
    {
        $customerCode = $customer->customer_code ?: "id{$customer->id_customer}";
        $labelSlug = Str::slug($label);

        $folder = "customer_document/{$customerCode}/onboarding";
        $fileName = "{$customerCode}.onboarding.lainnya.{$labelSlug}.{$extension}";

        return [$folder, $fileName];
    }
}
