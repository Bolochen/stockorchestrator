<?php

namespace App\Services;

use App\Models\Stock;

class StockQueryService
{
    public function getCurrentStock()
    {
        return Stock::with(['product', 'warehouse'])
            ->whereHas('product', function ($query) {
                $query->where('is_active', 1);
            })
            ->latest()
            ->get();
    }

    public function getWarehouseStock(int $warehouseId)
    {
        return Stock::with(['product', 'warehouse'])
            ->where('warehouse_id', $warehouseId)
            ->whereHas('product', function ($query) {
                $query->where('is_active', 1);
            })
            ->get();
    }

    public function getProductStock(int $productId)
    {
        return Stock::with(['product', 'warehouse'])
            ->where('product_id', $productId)
            ->whereHas('product', function ($query) {
                $query->where('is_active', 1);
            })
            ->get();
    }

    public function getLowStockItems(?int $warehouseId = null)
    {
        return Stock::with(['product', 'warehouse'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->whereHas('product', function ($query) {
                $query->whereColumn('stocks.quantity', '<=', 'products.minimum_stock')->where('is_active', 1);
            })->get();
    }

    public function getStockSummaryByWarehouse(int $warehouseId)
    {
        $summary = Stock::where('warehouse_id', $warehouseId)
            ->whereHas('product', function ($query) {
                $query->where('is_active', 1);
            })
            ->selectRaw('COUNT(*) as total_items')
            ->selectRaw('COALESCE(SUM(quantity), 0) as total_quantity')
            ->selectRaw('COALESCE(SUM(total_value), 0) as total_value')
            ->first();

        return [
            'warehouse_id' => $warehouseId,
            'total_items' => (int) $summary->total_items,
            'total_quantity' => (int) $summary->total_quantity,
            'total_value' => (string) $summary->total_value,
        ];
    }
}
