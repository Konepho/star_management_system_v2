<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\Student;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class PosOwnerLookupService
{
    public function findOwnerByIdentifier(?string $identifier): ?Model
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return null;
        }

        $student = Student::query()
            ->where('admission_no', $identifier)
            ->where('status', Student::STATUS_ACTIVE)
            ->first();

        if ($student) {
            return $student;
        }

        return Staff::query()
            ->where('staff_no', $identifier)
            ->whereIn('status', ['active', 'on-leave'])
            ->first();
    }

    public function walletForOwner(Model $owner): Wallet
    {
        return Wallet::query()->firstOrCreate(
            [
                'owner_type' => $owner::class,
                'owner_id' => $owner->getKey(),
            ],
            [
                'current_balance' => 0,
                'status' => Wallet::STATUS_ACTIVE,
            ],
        );
    }

    public function ensureWalletIsTransactable(Wallet $wallet, string $errorKey = 'wallet_id'): void
    {
        $wallet->loadMissing('owner');

        if ($wallet->status !== Wallet::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                $errorKey => 'Selected wallet is not active for POS transactions.',
            ]);
        }

        $owner = $wallet->owner;

        $isEligibleOwner = match (true) {
            $owner instanceof Student => $owner->status === Student::STATUS_ACTIVE,
            $owner instanceof Staff => in_array((string) $owner->status, ['active', 'on-leave'], true),
            default => false,
        };

        if (! $isEligibleOwner) {
            throw ValidationException::withMessages([
                $errorKey => 'Selected wallet owner is not active for POS transactions.',
            ]);
        }
    }
}
