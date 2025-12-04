<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockLog;
use Illuminate\Http\Request;

class StockLogController extends Controller
{
    /**
     * Display a listing of stock logs.
     */
    public function index(Request $request)
    {
        $query = StockLog::with('stock', 'product');

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by stock_id
        if ($request->has('stock_id')) {
            $query->where('stock_id', $request->stock_id);
        }

        // Filter by product_id
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Order by latest first
        $stockLogs = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $stockLogs,
            'count' => $stockLogs->count()
        ]);
    }

    /**
     * Display the specified stock log.
     */
    public function show(string $id)
    {
        $stockLog = StockLog::with('stock', 'product')->find($id);

        if (!$stockLog) {
            return response()->json([
                'success' => false,
                'message' => 'Stock log not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $stockLog
        ]);
    }
}
