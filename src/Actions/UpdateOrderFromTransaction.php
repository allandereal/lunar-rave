<?php

namespace Lunar\Flutterwave\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lunar\Flutterwave\DataTransferObjects\FlutterwaveTransaction;
use Lunar\Models\Order;

class UpdateOrderFromTransaction
{
    final public static function execute(
        ?Order $order,
        ?\stdClass $transaction,
        string $successStatus = 'successful',
        string $failStatus = 'failed'
    ): Order {
        return DB::transaction(function () use ($order, $transaction) {
            $order = app(StoreTransaction::class)->store($order, $transaction);

            $statuses = config('lunar.flutterwave.status_mapping', []);

            $placedAt = null;

            if ($transaction->status == FlutterwaveTransaction::STATUS_SUCCESSFUL) {
                $placedAt = now();
            }

            $order->update([
                'status' => $statuses[$transaction->status] ?? $transaction->status,
                'placed_at' => $order->placed_at ?: $placedAt,
            ]);

            return $order->refresh();
        });
    }
}
