<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->when($request->query('symbol'), fn($q, $v) => $q->where('symbol', $v))
            ->when($request->query('side'), fn($q, $v) => $q->where('side', $v))
            ->when($request->query('status'), fn($q, $v) => $q->where('status', $v))
            ->orderBy('created_at', 'desc')
            ->get();

        return OrderResource::collection($orders);
    }

    public function orderbook(Request $request): AnonymousResourceCollection
    {
        $symbol = $request->query('symbol');

        $orders = Order::where('status', Order::STATUS_OPEN)
            ->when($symbol, fn($q) => $q->where('symbol', $symbol))
            ->orderByRaw("CASE WHEN side = 'buy' THEN price END DESC")
            ->orderByRaw("CASE WHEN side = 'sell' THEN price END ASC")
            ->orderBy('created_at', 'asc')
            ->get();

        return OrderResource::collection($orders);
    }

    public function store(PlaceOrderRequest $request): OrderResource
    {
        $order = $this->orderService->placeOrder(
            $request->user(),
            $request->validated()
        );

        return new OrderResource($order);
    }

    public function cancel(Request $request, int $id): OrderResource
    {
        $order = $this->orderService->cancelOrder($request->user(), $id);

        return new OrderResource($order);
    }
}

