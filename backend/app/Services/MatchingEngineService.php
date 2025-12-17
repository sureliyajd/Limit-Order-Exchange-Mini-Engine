<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Trade;
use LogicException;

class MatchingEngineService
{
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
     * @throws LogicException
     */
    public function match(Order $order): ?Trade
    {
        throw new LogicException('Not implemented');
    }
}

