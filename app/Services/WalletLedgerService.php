<?php

namespace App\Services;

use App\Models\PosProduct;
use App\Models\PosSale;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletLedgerService
{
    public function __construct(
        protected DocumentNumberService $documentNumberService,
    ) {
    }

    public function topup(Wallet $wallet, float $amount, string $paymentMethod, ?string $notes = null): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $amount, $paymentMethod, $notes): WalletTransaction {
            $wallet = $wallet->newQuery()->lockForUpdate()->with('owner')->findOrFail($wallet->id);
            $balanceBefore = (float) $wallet->current_balance;
            $balanceAfter = $balanceBefore + $amount;
            $owner = $wallet->owner;
            $academicYear = method_exists($owner, 'currentEnrollment') ? $owner?->currentEnrollment()?->academicYear : null;

            $wallet->forceFill([
                'current_balance' => $balanceAfter,
                'status' => Wallet::STATUS_ACTIVE,
            ])->save();

            return WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'transaction_no' => $this->documentNumberService->nextWalletTopupReceiptNumber($academicYear),
                'transaction_type' => WalletTransaction::TYPE_TOPUP,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => WalletTransaction::STATUS_POSTED,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
                'performed_by_user_id' => auth()->id(),
            ]);
        });
    }

    public function adjust(Wallet $wallet, float $amountDelta, string $reason, ?string $notes = null): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $amountDelta, $reason, $notes): WalletTransaction {
            $wallet = $wallet->newQuery()->lockForUpdate()->findOrFail($wallet->id);
            $balanceBefore = (float) $wallet->current_balance;
            $balanceAfter = $balanceBefore + $amountDelta;

            if ($balanceAfter < 0) {
                throw ValidationException::withMessages([
                    'amount_delta' => 'Wallet balance cannot go below zero after adjustment.',
                ]);
            }

            $wallet->forceFill([
                'current_balance' => $balanceAfter,
                'status' => Wallet::STATUS_ACTIVE,
            ])->save();

            return WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'transaction_no' => null,
                'transaction_type' => WalletTransaction::TYPE_ADJUSTMENT,
                'amount' => $amountDelta,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => WalletTransaction::STATUS_POSTED,
                'notes' => trim($reason . ($notes ? ' - ' . $notes : '')),
                'performed_by_user_id' => auth()->id(),
            ]);
        });
    }

    /**
     * @param  Collection<int, array{product: PosProduct, quantity: int}>  $lines
     */
    public function createSale(Wallet $wallet, Collection $lines, ?string $notes = null): PosSale
    {
        return DB::transaction(function () use ($wallet, $lines, $notes): PosSale {
            $wallet = $wallet->newQuery()->lockForUpdate()->with('owner')->findOrFail($wallet->id);

            $productIds = $lines
                ->pluck('product.id')
                ->map(fn (mixed $id) => (int) $id)
                ->unique()
                ->values();

            /** @var Collection<int, PosProduct> $lockedProducts */
            $lockedProducts = PosProduct::query()
                ->lockForUpdate()
                ->whereIn('id', $productIds->all())
                ->get()
                ->keyBy('id');

            $balanceBefore = (float) $wallet->current_balance;
            $normalizedLines = $lines
                ->map(function (array $line) use ($lockedProducts): ?array {
                    /** @var PosProduct|null $product */
                    $product = $lockedProducts->get((int) $line['product']->id);

                    if (! $product) {
                        return null;
                    }

                    return [
                        'product' => $product,
                        'quantity' => (int) $line['quantity'],
                    ];
                })
                ->filter()
                ->values();

            $total = (float) $normalizedLines->sum(fn (array $line) => ((float) $line['product']->price) * $line['quantity']);

            if ($total <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'Select at least one valid product to continue.',
                ]);
            }

            if ($balanceBefore < $total) {
                throw ValidationException::withMessages([
                    'wallet_id' => 'Wallet balance is not enough for this sale.',
                ]);
            }

            foreach ($normalizedLines as $line) {
                if ($line['product']->status !== 'active') {
                    throw ValidationException::withMessages([
                        'items' => $line['product']->name . ' is inactive and cannot be sold.',
                    ]);
                }

                if ($line['quantity'] > $line['product']->stock_quantity) {
                    throw ValidationException::withMessages([
                        'items' => 'Insufficient stock for ' . $line['product']->name . '.',
                    ]);
                }
            }

            $balanceAfter = $balanceBefore - $total;
            $owner = $wallet->owner;
            $academicYear = method_exists($owner, 'currentEnrollment') ? $owner?->currentEnrollment()?->academicYear : null;

            $sale = PosSale::query()->create([
                'sale_no' => $this->documentNumberService->nextPosSaleNumber($academicYear),
                'wallet_id' => $wallet->id,
                'owner_type' => $wallet->owner_type,
                'owner_id' => $wallet->owner_id,
                'total_amount' => $total,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => PosSale::STATUS_POSTED,
                'notes' => $notes,
                'performed_by_user_id' => auth()->id(),
            ]);

            foreach ($normalizedLines as $line) {
                $product = $line['product'];
                $quantity = $line['quantity'];
                $unitPrice = (float) $product->price;

                $sale->items()->create([
                    'pos_product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $quantity,
                ]);

                $product->decrement('stock_quantity', $quantity);
            }

            $wallet->forceFill([
                'current_balance' => $balanceAfter,
            ])->save();

            WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'transaction_no' => $sale->sale_no,
                'transaction_type' => WalletTransaction::TYPE_SALE,
                'amount' => $total,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => WalletTransaction::STATUS_POSTED,
                'notes' => $notes,
                'performed_by_user_id' => auth()->id(),
                'reference_type' => $sale->getMorphClass(),
                'reference_id' => $sale->id,
            ]);

            return $sale->load(['wallet.owner', 'items.product']);
        });
    }

    public function reverseSale(PosSale $sale, ?string $reason = null): void
    {
        DB::transaction(function () use ($sale, $reason): void {
            $sale->refresh();

            if ($sale->isReversed()) {
                return;
            }

            $wallet = $sale->wallet()->lockForUpdate()->firstOrFail();
            $wallet->refresh();

            $balanceBefore = (float) $wallet->current_balance;
            $balanceAfter = $balanceBefore + (float) $sale->total_amount;

            foreach ($sale->items as $item) {
                $item->product?->increment('stock_quantity', $item->quantity);
            }

            $wallet->forceFill([
                'current_balance' => $balanceAfter,
            ])->save();

            $sale->forceFill([
                'status' => PosSale::STATUS_REVERSED,
                'reversed_at' => now(),
                'reversal_reason' => $reason ?: 'Reversed from POS sales management.',
            ])->save();

            $sale->wallet
                ->transactions()
                ->where('reference_type', $sale->getMorphClass())
                ->where('reference_id', $sale->id)
                ->where('transaction_type', WalletTransaction::TYPE_SALE)
                ->where('status', WalletTransaction::STATUS_POSTED)
                ->update([
                    'status' => WalletTransaction::STATUS_REVERSED,
                    'reversed_at' => now(),
                    'reversal_reason' => $reason ?: 'Sale reversed.',
                ]);

            WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'transaction_no' => null,
                'transaction_type' => WalletTransaction::TYPE_REVERSAL,
                'amount' => (float) $sale->total_amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => WalletTransaction::STATUS_POSTED,
                'notes' => $reason ?: 'Reversed sale ' . $sale->sale_no . '.',
                'performed_by_user_id' => auth()->id(),
                'reference_type' => $sale->getMorphClass(),
                'reference_id' => $sale->id,
            ]);
        });
    }

    public function reverseTopup(WalletTransaction $transaction, ?string $reason = null): void
    {
        DB::transaction(function () use ($transaction, $reason): void {
            $transaction = WalletTransaction::query()
                ->lockForUpdate()
                ->with('wallet')
                ->findOrFail($transaction->id);

            if ($transaction->isReversed()) {
                return;
            }

            if ($transaction->transaction_type !== WalletTransaction::TYPE_TOPUP) {
                throw ValidationException::withMessages([
                    'transaction' => 'Only top-up transactions can be reversed here.',
                ]);
            }

            $wallet = $transaction->wallet()->lockForUpdate()->firstOrFail();
            $balanceBefore = (float) $wallet->current_balance;
            $amount = (float) $transaction->amount;
            $balanceAfter = $balanceBefore - $amount;

            if ($balanceAfter < 0) {
                throw ValidationException::withMessages([
                    'transaction' => 'Wallet balance is too low to reverse this top-up.',
                ]);
            }

            $wallet->forceFill([
                'current_balance' => $balanceAfter,
            ])->save();

            $transaction->forceFill([
                'status' => WalletTransaction::STATUS_REVERSED,
                'reversed_at' => now(),
                'reversal_reason' => $reason ?: 'Top-up reversed from wallet management.',
            ])->save();

            WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'transaction_no' => null,
                'transaction_type' => WalletTransaction::TYPE_REVERSAL,
                'amount' => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => WalletTransaction::STATUS_POSTED,
                'notes' => $reason ?: 'Reversed top-up ' . ($transaction->transaction_no ?: '#' . $transaction->id) . '.',
                'performed_by_user_id' => auth()->id(),
                'reference_type' => $transaction->getMorphClass(),
                'reference_id' => $transaction->id,
            ]);
        });
    }
}
