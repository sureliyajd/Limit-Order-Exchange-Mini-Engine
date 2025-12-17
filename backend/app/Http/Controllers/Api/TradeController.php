<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trade;
use Illuminate\Http\Request;

class TradeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        
        $trades = Trade::with(['buyOrder.user', 'sellOrder.user'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $trades->items(),
            'meta' => [
                'current_page' => $trades->currentPage(),
                'last_page' => $trades->lastPage(),
                'per_page' => $trades->perPage(),
                'total' => $trades->total(),
            ],
        ]);
    }

    public function summary()
    {
        $totalTrades = Trade::count();
        $totalVolume = Trade::sum('usd_volume');
        $totalCommission = Trade::sum('commission');

        return response()->json([
            'data' => [
                'total_trades' => $totalTrades,
                'total_volume' => number_format($totalVolume, 2),
                'total_commission' => number_format($totalCommission, 2),
                'commission_rate' => '1.5%',
            ],
        ]);
    }
}
