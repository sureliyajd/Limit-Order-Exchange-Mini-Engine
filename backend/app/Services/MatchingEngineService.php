<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MatchingEngineService
{
    private const COMMISSION_RATE = '0.015';

    /**
     * Attempt to match the given order against existing orders.
     *
     * For buy orders: finds the oldest sell order where sell.price <= buy.price
     * For sell orders: finds the oldest buy order where buy.price >= sell.price
     *
     * Only full matches are executed (no partial fills).
     * On match: executes trade, transfers funds/assets, applies commission.
     *
     * @param Order $order The newly created order to match
     * @return Trade|null The executed trade if matched, null otherwise
     */
    public function match(Order $order): ?Trade
    {
        return DB::transaction(function () use ($order) {
            // Lock and refresh the incoming order
            $order = Order::lockForUpdate()->find($order->id);

            if (!$order || $order->status !== Order::STATUS_OPEN) {
                return null;
            }

            // Find counter order
            $counterOrder = $this->findCounterOrder($order);

            if (!$counterOrder) {
                return null;
            }

            // Determine buyer and seller
            if ($order->side === Order::SIDE_BUY) {
                $buyOrder = $order;
                $sellOrder = $counterOrder;
            } else {
                $buyOrder = $counterOrder;
                $sellOrder = $order;
            }

            // Lock all required rows in consistent order to prevent deadlocks
            $buyer = User::lockForUpdate()->find($buyOrder->user_id);
            $seller = User::lockForUpdate()->find($sellOrder->user_id);

            $buyerAsset = Asset::lockForUpdate()
                ->where('user_id', $buyer->id)
                ->where('symbol', $order->symbol)
                ->first();

            $sellerAsset = Asset::lockForUpdate()
                ->where('user_id', $seller->id)
                ->where('symbol', $order->symbol)
                ->first();

            // Calculate settlement amounts (use sell order price as execution price)
            $executionPrice = $sellOrder->price;
            $amount = $order->amount;
            $usdVolume = bcmul($executionPrice, $amount, 8);
            $commission = bcmul($usdVolume, self::COMMISSION_RATE, 8);

            // Settlement: Buyer pays commission, receives asset
            // Buyer already locked USD when placing buy order, now deduct commission
            $buyer->balance = bcsub($buyer->balance, $commission, 8);
            $buyer->save();

            // Seller receives full USD
            $seller->balance = bcadd($seller->balance, $usdVolume, 8);
            $seller->save();

            // Buyer receives asset
            if (!$buyerAsset) {
                $buyerAsset = Asset::create([
                    'user_id' => $buyer->id,
                    'symbol' => $order->symbol,
                    'amount' => '0',
                    'locked_amount' => '0',
                ]);
            }
            $buyerAsset->amount = bcadd($buyerAsset->amount, $amount, 8);
            $buyerAsset->save();

            // Seller's locked asset is deducted
            $sellerAsset->locked_amount = bcsub($sellerAsset->locked_amount, $amount, 8);
            $sellerAsset->save();

            // Update both orders to FILLED
            $buyOrder->status = Order::STATUS_FILLED;
            $buyOrder->save();

            $sellOrder->status = Order::STATUS_FILLED;
            $sellOrder->save();

            // Create trade record
            return Trade::create([
                'buy_order_id' => $buyOrder->id,
                'sell_order_id' => $sellOrder->id,
                'symbol' => $order->symbol,
                'price' => $executionPrice,
                'amount' => $amount,
                'usd_volume' => $usdVolume,
                'commission' => $commission,
            ]);
        });
    }

    private function findCounterOrder(Order $order): ?Order
    {
        $query = Order::lockForUpdate()
            ->where('symbol', $order->symbol)
            ->where('status', Order::STATUS_OPEN)
            ->where('amount', $order->amount) // Full match only
            ->where('user_id', '!=', $order->user_id); // Can't match own orders

        if ($order->side === Order::SIDE_BUY) {
            // Find sell orders where sell.price <= buy.price
            $query->where('side', Order::SIDE_SELL)
                ->where('price', '<=', $order->price)
                ->orderBy('price', 'asc') // Best price first
                ->orderBy('created_at', 'asc'); // FIFO
        } else {
            // Find buy orders where buy.price >= sell.price
            $query->where('side', Order::SIDE_BUY)
                ->where('price', '>=', $order->price)
                ->orderBy('price', 'desc') // Best price first
                ->orderBy('created_at', 'asc'); // FIFO
        }

        return $query->first();
    }
}
