<?php

namespace App\Actions\Customer;

use App\Models\Customer;
use App\Models\CustomerOwnershipMigration;
use Illuminate\Support\Facades\DB;

class MigrateCustomerOwnershipAction
{
    /**
     * Reassign id_user for the given customers to $toUserId, recording one
     * customer_ownership_migrations row per customer actually moved.
     * Customers already owned by $toUserId are skipped (no-op, no audit row).
     *
     * @param  int[]  $customerIds
     * @return int  number of customers actually migrated
     */
    public function execute(array $customerIds, int $toUserId, ?int $migratedBy): int
    {
        return DB::transaction(function () use ($customerIds, $toUserId, $migratedBy) {
            $migratedCount = 0;

            foreach ($customerIds as $customerId) {
                $fromUserId = Customer::where('id_customer', $customerId)->value('id_user');

                if ($fromUserId === $toUserId) {
                    continue;
                }

                Customer::where('id_customer', $customerId)->update(['id_user' => $toUserId]);

                CustomerOwnershipMigration::create([
                    'id_customer'  => $customerId,
                    'from_user_id' => $fromUserId,
                    'to_user_id'   => $toUserId,
                    'migrated_by'  => $migratedBy,
                    'migrated_at'  => now(),
                ]);

                $migratedCount++;
            }

            return $migratedCount;
        });
    }
}
