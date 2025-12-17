<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;

class OrderService
{
    private const ALLOWED_SYMBOLS = ['BTC', 'ETH'];

    public function __construct(
        protected MatchingEngineService $matchingEngine
    ) {}

    /**
     * Place a new limit order for the user.
     *
     * Validates balance/asset availability, locks funds, creates the order,
     * and triggers the matching engine.
     *
     * @param User $user The authenticated user placing the order
     * @param array $data Order data containing: symbol, side, price, amount
     * @return Order The created order
     * @throws InvalidArgumentException
     */
    public function placeOrder(User $user, array $data): Order
    {
        $symbol = $data['symbol'] ?? null;
        $side = $data['side'] ?? null;
        $price = $data['price'] ?? null;
        $amount = $data['amount'] ?? null;

        // Validate inputs
        if (!in_array($symbol, self::ALLOWED_SYMBOLS, true)) {
            throw new InvalidArgumentException('Invalid symbol. Allowed: BTC, ETH');
        }

        if (!in_array($side, [Order::SIDE_BUY, Order::SIDE_SELL], true)) {
            throw new InvalidArgumentException('Invalid side. Allowed: buy, sell');
        }

        if (!is_numeric($price) || bccomp((string) $price, '0', 8) <= 0) {
            throw new InvalidArgumentException('Price must be greater than 0');
        }

        if (!is_numeric($amount) || bccomp((string) $amount, '0', 8) <= 0) {
            throw new InvalidArgumentException('Amount must be greater than 0');
        }

        $price = (string) $price;
        $amount = (string) $amount;

        return DB::transaction(function () use ($user, $symbol, $side, $price, $amount) {
            if ($side === Order::SIDE_BUY) {
                return $this->placeBuyOrder($user, $symbol, $price, $amount);
            }

            return $this->placeSellOrder($user, $symbol, $price, $amount);
        });
    }

    private function placeBuyOrder(User $user, string $symbol, string $price, string $amount): Order
    {
        // Lock user row
        $user = User::lockForUpdate()->find($user->id);

        $totalCost = bcmul($price, $amount, 8);

        if (bccomp($user->balance, $totalCost, 8) < 0) {
            throw new InvalidArgumentException('Insufficient USD balance');
        }

        // Deduct from balance
        $user->balance = bcsub($user->balance, $totalCost, 8);
        $user->save();

        // Create order
        return Order::create([
            'user_id' => $user->id,
            'symbol' => $symbol,
            'side' => Order::SIDE_BUY,
            'price' => $price,
            'amount' => $amount,
            'status' => Order::STATUS_OPEN,
        ]);
    }

    private function placeSellOrder(User $user, string $symbol, string $price, string $amount): Order
    {
        // Lock asset row (or create if doesn't exist)
        $asset = Asset::lockForUpdate()
            ->where('user_id', $user->id)
            ->where('symbol', $symbol)
            ->first();

        if (!$asset || bccomp($asset->amount, $amount, 8) < 0) {
            throw new InvalidArgumentException('Insufficient asset balance');
        }

        // Move from available to locked
        $asset->amount = bcsub($asset->amount, $amount, 8);
        $asset->locked_amount = bcadd($asset->locked_amount, $amount, 8);
        $asset->save();

        // Create order
        return Order::create([
            'user_id' => $user->id,
            'symbol' => $symbol,
            'side' => Order::SIDE_SELL,
            'price' => $price,
            'amount' => $amount,
            'status' => Order::STATUS_OPEN,
        ]);
    }

    /**
     * Cancel an open order and release locked funds.
     *
     * For buy orders: releases locked USD back to user balance.
     * For sell orders: releases locked assets back to available amount.
     *
     * @param User $user The authenticated user cancelling the order
     * @param int $orderId The ID of the order to cancel
     * @return Order The cancelled order
     * @throws LogicException
     */
    public function cancelOrder(User $user, int $orderId): Order
    {
        throw new LogicException('Not implemented');
    }
}
