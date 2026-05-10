<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use App\Services\StockQueryService;
use App\Services\StockValuationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function __construct(
        private StockQueryService $stockQueries,
        private StockMovementService $stockMovements,
        private StockValuationService $valuation,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse|Response
    {
        $validated = $request->validate([
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'low_stock' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('low_stock')) {
            $stocks = $this->stockQueries->getLowStockItems($validated['warehouse_id'] ?? null);
        } elseif (isset($validated['warehouse_id'])) {
            $stocks = $this->stockQueries->getWarehouseStock($validated['warehouse_id']);
        } elseif (isset($validated['product_id'])) {
            $stocks = $this->stockQueries->getProductStock($validated['product_id']);
        } else {
            $stocks = $this->stockQueries->getCurrentStock();
        }

        $summary = isset($validated['warehouse_id'])
            ? $this->stockQueries->getStockSummaryByWarehouse($validated['warehouse_id'])
            : null;

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $stocks,
                'summary' => $summary,
            ]);
        }

        return Inertia::render('stocks/index', [
            'stocks' => $stocks,
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'code', 'name']),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'sku', 'name']),
            'filters' => [
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'product_id' => $validated['product_id'] ?? null,
                'low_stock' => $request->boolean('low_stock'),
            ],
            'summary' => $summary,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('stocks/create', [
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'code', 'name']),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'sku', 'name']),
            'movementTypes' => ['RESTOCK', 'SALE', 'TRANSFER', 'ADJUSTMENT'],
            'defaultMovementNumber' => 'MV'.now()->format('ymdHis'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate($this->movementRules());

        $movement = $this->stockMovements->createDraft($validated['movement']);

        foreach ($validated['details'] as $detail) {
            $this->stockMovements->addDetail($movement, $detail);
        }

        if ($request->boolean('post')) {
            $movement = $this->stockMovements->post($movement);
        }

        $movement = $movement->fresh(['details']);

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $movement,
            ], 201);
        }

        return to_route('stocks.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Stock $stock): JsonResponse|Response
    {
        $stock->load(['product', 'warehouse']);
        $valuation = [
            'total_value' => $this->valuation->calculateTotalValue(
                $stock->quantity,
                (string) $stock->average_cost
            ),
        ];

        if (request()->wantsJson()) {
            return response()->json([
                'data' => $stock,
                'valuation' => $valuation,
            ]);
        }

        return Inertia::render('stocks/show', [
            'stock' => $stock,
            'valuation' => $valuation,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Stock $stock): Response
    {
        $stock->load(['product', 'warehouse']);

        return Inertia::render('stocks/edit', [
            'stock' => $stock,
            'defaultMovementNumber' => 'ADJ'.now()->format('ymdHis'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Stock $stock): JsonResponse
    {
        abort(405, 'Direct stock updates are not allowed. Use stock movements.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Stock $stock): JsonResponse
    {
        abort(405, 'Direct stock deletion is not allowed. Use stock movements.');
    }

    public function warehouse(int $warehouseId): JsonResponse
    {
        return response()->json([
            'data' => $this->stockQueries->getWarehouseStock($warehouseId),
            'summary' => $this->stockQueries->getStockSummaryByWarehouse($warehouseId),
        ]);
    }

    public function product(int $productId): JsonResponse
    {
        return response()->json([
            'data' => $this->stockQueries->getProductStock($productId),
        ]);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
        ]);

        return response()->json([
            'data' => $this->stockQueries->getLowStockItems($validated['warehouse_id'] ?? null),
        ]);
    }

    public function summary(int $warehouseId): JsonResponse
    {
        return response()->json([
            'data' => $this->stockQueries->getStockSummaryByWarehouse($warehouseId),
        ]);
    }

    public function postMovement(StockMovement $movement): JsonResponse
    {
        return response()->json([
            'data' => $this->stockMovements->post($movement),
        ]);
    }

    public function cancelMovement(StockMovement $movement): JsonResponse
    {
        return response()->json([
            'data' => $this->stockMovements->cancel($movement),
        ]);
    }

    public function transfer(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->has('movement')) {
            $request->merge([
                'source_warehouse_id' => $request->input('movement.warehouse_id'),
                'destination_warehouse_id' => $request->input('movement.destination_warehouse_id'),
            ]);
        }

        $validated = $request->validate([
            'source_warehouse_id' => ['required', 'integer', 'exists:warehouses,id', 'different:destination_warehouse_id'],
            'destination_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'details.*.quantity' => ['required', 'integer', 'min:1'],
            'details.*.notes' => ['nullable', 'string'],
        ]);

        $transfer = $this->stockMovements->transfer(
            $validated['source_warehouse_id'],
            $validated['destination_warehouse_id'],
            $validated['details']
        );

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $transfer,
            ], 201);
        }

        return to_route('stocks.index');
    }

    public function valuationPreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'old_quantity' => ['required', 'integer', 'min:0'],
            'old_average_cost' => ['required', 'numeric', 'min:0'],
            'incoming_quantity' => ['required', 'integer', 'min:0'],
            'incoming_unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $averageCost = $this->valuation->calculateMovingAverage(
            $validated['old_quantity'],
            (string) $validated['old_average_cost'],
            $validated['incoming_quantity'],
            (string) $validated['incoming_unit_cost']
        );

        return response()->json([
            'average_cost' => $averageCost,
            'total_value' => $this->valuation->calculateTotalValue(
                $validated['old_quantity'] + $validated['incoming_quantity'],
                $averageCost
            ),
        ]);
    }

    private function movementRules(): array
    {
        return [
            'post' => ['nullable', 'boolean'],
            'movement.movement_number' => ['required', 'string', 'max:20', 'unique:stock_movements,movement_number'],
            'movement.type' => ['required', Rule::in(['RESTOCK', 'SALE', 'TRANSFER_IN', 'TRANSFER_OUT', 'ADJUSTMENT'])],
            'movement.warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'movement.destination_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id', 'different:movement.warehouse_id'],
            'movement.reference_number' => ['nullable', 'string', 'max:20'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'details.*.quantity' => ['required', 'integer', 'not_in:0'],
            'details.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'details.*.notes' => ['nullable', 'string'],
        ];
    }
}
