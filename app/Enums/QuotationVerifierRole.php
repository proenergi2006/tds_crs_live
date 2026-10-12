<?php

namespace App\Enums;

use App\Models\User;

enum QuotationVerifierRole: int
{
    case BranchManager            = 8;
    case BranchManagerPolimer     = 17;
    case BranchManagerProenergi   = 15;
    case OperationManager         = 10;
    case OperationManagerProenergi = 16;
    case Ceo                      = 2;

    public static function forQuotation(QuotationBrand $brand, bool $isPolimer, PriceTier $tier): self
    {
        $chain = match (true) {
            $brand === QuotationBrand::Proenergi => [self::BranchManagerProenergi, self::OperationManagerProenergi, self::Ceo],
            $isPolimer                           => [self::BranchManagerPolimer, self::OperationManager, self::Ceo],
            default                              => [self::BranchManager, self::OperationManager, self::Ceo],
        };

        return match ($tier) {
            PriceTier::Tier1 => $chain[0],
            PriceTier::Tier2 => $chain[1],
            default          => $chain[2],
        };
    }

    public function permission(): string
    {
        return match ($this) {
            self::BranchManager             => 'penawaran.verify-bm',
            self::BranchManagerPolimer      => 'penawaran.verify-bm-polimer',
            self::OperationManager          => 'penawaran.verify-om',
            self::BranchManagerProenergi    => 'penawaran.proenergi.verify-bm',
            self::OperationManagerProenergi => 'penawaran.proenergi.verify-om',
            self::Ceo                       => 'penawaran.verify-ceo',
        };
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

    public function urlSegment(): string
    {
        return match ($this) {
            self::BranchManager, self::BranchManagerPolimer, self::BranchManagerProenergi => 'bm',
            self::OperationManager, self::OperationManagerProenergi                       => 'om',
            self::Ceo                                                                     => 'ceo',
        };
    }

    public function isHeldBy(User $user): bool
    {
        return $user->hasRole('Administrator')
            || ($user->hasRole($this->value) && $user->can($this->permission()));
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
