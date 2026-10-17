<?php

namespace App\Enums;

use App\Models\User;

enum QuotationVerifierRole: int
{
    case BranchManager             = 8;
    case BranchManagerPolimer      = 17;
    case BranchManagerProenergi    = 15;
    case OperationManager          = 10;
    case OperationManagerProenergi = 16;
    case Ceo                       = 2;

    public static function forQuotation(QuotationBrand $brand, bool $isPolimer, PriceTier $tier): self
    {
        $chain = $brand === QuotationBrand::Proenergi
            ? [self::branchManagerFor($brand, $isPolimer), self::OperationManagerProenergi, self::Ceo]
            : [self::branchManagerFor($brand, $isPolimer), self::OperationManager, self::Ceo];

        return match ($tier) {
            PriceTier::Tier1 => $chain[0],
            PriceTier::Tier2 => $chain[1],
            PriceTier::Tier3 => $chain[2],
        };
    }

    public static function branchManagerFor(QuotationBrand $brand, bool $isPolimer): self
    {
        return match (true) {
            $brand === QuotationBrand::Proenergi => self::BranchManagerProenergi,
            $isPolimer                           => self::BranchManagerPolimer,
            default                              => self::BranchManager,
        };
    }

    public static function operationManagerFor(QuotationBrand $brand): self
    {
        return $brand === QuotationBrand::Proenergi ? self::OperationManagerProenergi : self::OperationManager;
    }

    public static function permission(QuotationBrand $brand): string
    {
        return $brand->verifyPermission();
    }

    public function label(): string
    {
        return match ($this) {
            self::BranchManager             => 'Branch Manager',
            self::BranchManagerPolimer      => 'Branch Manager Polimer',
            self::BranchManagerProenergi    => 'Branch Manager Proenergi',
            self::OperationManager          => 'Operation Manager',
            self::OperationManagerProenergi => 'Operation Manager Proenergi',
            self::Ceo                       => 'CEO',
        };
    }

    public function isHeldBy(User $user, QuotationBrand $brand): bool
    {
        return $user->hasRole('Administrator')
            || ($user->hasRole($this->value) && $user->can(self::permission($brand)));
    }

    public static function branchManagerRoles(): array
    {
        return [self::BranchManager->value, self::BranchManagerPolimer->value, self::BranchManagerProenergi->value];
    }

    public static function operationManagerRoles(): array
    {
        return [self::OperationManager->value, self::OperationManagerProenergi->value];
    }
}
