<?php

namespace App\Enums;

use App\Models\User;

enum QuotationBrand: string
{
    case Tds       = 'tds';
    case Proenergi = 'proenergi';

    public function label(): string
    {
        return match ($this) {
            self::Tds       => 'TDS',
            self::Proenergi => 'Proenergi',
        };
    }

    public function managePermission(): string
    {
        return match ($this) {
            self::Tds       => 'quotation.manage',
            self::Proenergi => 'quotation.proenergi.manage',
        };
    }

    public function viewAnyPermission(): string
    {
        return match ($this) {
            self::Tds       => 'quotation.viewAny',
            self::Proenergi => 'quotation.proenergi.viewAny',
        };
    }

    public function viewOwnPermission(): string
    {
        return match ($this) {
            self::Tds       => 'quotation.viewOwn',
            self::Proenergi => 'quotation.proenergi.viewOwn',
        };
    }

    public function verifyPermission(): string
    {
        return match ($this) {
            self::Tds       => 'quotation.verify',
            self::Proenergi => 'quotation.proenergi.verify',
        };
    }

    public function numberPrefix(): string
    {
        return match ($this) {
            self::Tds       => 'TDS-PN',
            self::Proenergi => 'PE-PN',
        };
    }

    public function pdfViewPrefix(): string
    {
        return match ($this) {
            self::Tds       => 'quotation.pdf',
            self::Proenergi => 'quotation.pdf_proenergi',
        };
    }

    public function logoPath(): string
    {
        return match ($this) {
            self::Tds       => public_path('images/logo-new.png'),
            self::Proenergi => public_path('images/logo-proenergi.png'),
        };
    }

    public static function managedBy(User $user): array
    {
        return self::allowing(fn (self $brand) => $user->can($brand->managePermission()));
    }

    public static function viewableBy(User $user): array
    {
        return self::allowing(fn (self $brand) => $user->can($brand->viewAnyPermission()) || $user->can($brand->viewOwnPermission()));
    }

    public static function viewableAnyBy(User $user): array
    {
        return self::allowing(fn (self $brand) => $user->can($brand->viewAnyPermission()));
    }

    public static function verifiableBy(User $user): array
    {
        return self::allowing(fn (self $brand) => $user->can($brand->verifyPermission()));
    }

    private static function allowing(callable $check): array
    {
        return array_values(array_filter(self::cases(), $check));
    }
}
