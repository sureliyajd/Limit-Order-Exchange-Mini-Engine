<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use LogicException;

class OrderService
{
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
     * @throws LogicException
     */
    public function placeOrder(User $user, array $data): Order
    {
        throw new LogicException('Not implemented');
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

