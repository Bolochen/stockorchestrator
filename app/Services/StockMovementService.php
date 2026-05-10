<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockMovementDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockMovementService
{
    public function __construct(private StockValuationService $valuation) {}

    public function createDraft(array $data): StockMovement
    {
        $data['status'] = 'DRAFT';

        return StockMovement::create($data);
    }

    public function addDetail(StockMovement $movement, array $data): StockMovementDetail
    {
        $this->ensureDraft($movement);

        $data['stock_movement_id'] = $movement->id;
        $data['total_cost'] = $this->valuation->calculateLineTotal(
            (int) $data['quantity'],
            (string) ($data['unit_cost'] ?? 0)
        );

        return $movement->details()->create($data);
    }

    public function post(StockMovement $movement): StockMovement
    {
        return DB::transaction(function () use ($movement) {
            $movement = StockMovement::with('details')
                ->lockForUpdate()
                ->findOrFail($movement->id);

            $this->ensureDraft($movement);

            if ($movement->details->isEmpty()) {
                throw new \InvalidArgumentException('Stock movement must have at least one detail.');
            }

            foreach ($movement->details as $detail) {
                match ($movement->type) {
                    'RESTOCK' => $this->applyRestock($movement, $detail),
                    'SALE' => $this->applySale($movement, $detail),
                    'TRANSFER_OUT' => $this->applyTransferOut($movement, $detail),
                    'TRANSFER_IN' => $this->applyTransferIn($movement, $detail),
                    'ADJUSTMENT' => $this->applyAdjustment($movement, $detail),
                    default => throw new \InvalidArgumentException("Unsupported stock movement type: {$movement->type}."),
                };
            }

            $movement->forceFill([
                'status' => 'POSTED',
                'posted_at' => now(),
            ])->save();

            return $movement->fresh(['details']);
        });
    }

    public function cancel(StockMovement $movement): StockMovement
    {
        $this->ensureDraft($movement);

        $movement->forceFill(['status' => 'CANCELLED'])->save();

        return $movement;
    }

    public function transfer(int $sourceWarehouseId, int $destinationWarehouseId, array $details): array
    {
        if ($sourceWarehouseId === $destinationWarehouseId) {
            throw new \InvalidArgumentException('Source and destination warehouse must be different.');
        }

        return DB::transaction(function () use ($sourceWarehouseId, $destinationWarehouseId, $details) {
            $transferOut = $this->createDraft([
                'movement_number' => $this->generateMovementNumber('TO'),
                'type' => 'TRANSFER_OUT',
                'warehouse_id' => $sourceWarehouseId,
                'destination_warehouse_id' => $destinationWarehouseId,
                'reference_number' => $this->generateMovementNumber('TR'),
            ]);

            $transferIn = $this->createDraft([
                'movement_number' => $this->generateMovementNumber('TI'),
                'type' => 'TRANSFER_IN',
                'warehouse_id' => $destinationWarehouseId,
                'destination_warehouse_id' => $sourceWarehouseId,
                'reference_number' => $transferOut->reference_number,
            ]);

            foreach ($details as $detail) {
                $this->addDetail($transferOut, $detail);
            }

            $this->post($transferOut);

            foreach ($transferOut->fresh('details')->details as $outDetail) {
                $this->addDetail($transferIn, [
                    'product_id' => $outDetail->product_id,
                    'quantity' => $outDetail->quantity,
                    'unit_cost' => $outDetail->unit_cost,
                    'total_cost' => $outDetail->total_cost,
                    'notes' => $outDetail->notes,
                ]);
            }

            $this->post($transferIn);

            return [
                'out' => $transferOut->fresh(['details']),
                'in' => $transferIn->fresh(['details']),
            ];
        });
    }

    private function applyRestock(StockMovement $movement, StockMovementDetail $detail): void
    {
        $quantity = $this->positiveQuantity($detail);
        $unitCost = (float) $detail->unit_cost;
        $incomingValue = $this->valuation->calculateLineTotal($quantity, (string) $unitCost);

        $stock = $this->findOrCreateStockForUpdate($movement->warehouse_id, $detail->product_id);
        $newQuantity = $stock->quantity + $quantity;
        $newTotalValue = (float) $stock->total_value + $incomingValue;

        $stock->forceFill([
            'quantity' => $newQuantity,
            'total_value' => $this->valuation->normalizeDecimal($newTotalValue),
            'average_cost' => $this->valuation->calculateMovingAverage(
                $stock->quantity,
                (string) $stock->average_cost,
                $quantity,
                (string) $unitCost
            ),
        ])->save();

        $this->updateDetailCost($detail, $unitCost, $incomingValue);
    }

    private function applySale(StockMovement $movement, StockMovementDetail $detail): void
    {
        $this->decreaseStockUsingAverageCost($movement->warehouse_id, $detail);
    }

    private function applyTransferOut(StockMovement $movement, StockMovementDetail $detail): void
    {
        $this->decreaseStockUsingAverageCost($movement->warehouse_id, $detail);
    }

    private function applyTransferIn(StockMovement $movement, StockMovementDetail $detail): void
    {
        $this->applyRestock($movement, $detail);
    }

    private function applyAdjustment(StockMovement $movement, StockMovementDetail $detail): void
    {
        if (empty($movement->reference_number) && empty($detail->notes)) {
            throw new \InvalidArgumentException('Adjustment requires a reference number or detail notes.');
        }

        if ($detail->quantity > 0) {
            $this->applyRestock($movement, $detail);

            return;
        }

        if ($detail->quantity < 0) {
            $this->decreaseStockUsingAverageCost($movement->warehouse_id, $detail, abs($detail->quantity));

            return;
        }

        throw new \InvalidArgumentException('Adjustment quantity cannot be zero.');
    }

    private function decreaseStockUsingAverageCost(
        int $warehouseId,
        StockMovementDetail $detail,
        ?int $quantity = null
    ): void {
        $quantity ??= $this->positiveQuantity($detail);
        $stock = $this->findStockForUpdate($warehouseId, $detail->product_id);

        if (! $stock || $stock->quantity < $quantity) {
            throw new \InvalidArgumentException('Stock quantity is not enough.');
        }

        $unitCost = (float) $stock->average_cost;
        $totalCost = $this->valuation->calculateSaleCost($quantity, (string) $stock->average_cost);
        $newQuantity = $stock->quantity - $quantity;

        $stock->forceFill([
            'quantity' => $newQuantity,
            'total_value' => $this->valuation->calculateTotalValue($newQuantity, (string) $stock->average_cost),
        ])->save();

        $this->updateDetailCost($detail, $unitCost, $totalCost);
    }

    private function findStockForUpdate(int $warehouseId, int $productId): ?Stock
    {
        return Stock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();
    }

    private function findOrCreateStockForUpdate(int $warehouseId, int $productId): Stock
    {
        $stock = $this->findStockForUpdate($warehouseId, $productId);

        if ($stock) {
            return $stock;
        }

        return Stock::create([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'quantity' => 0,
            'average_cost' => 0,
            'total_value' => 0,
        ]);
    }

    private function updateDetailCost(StockMovementDetail $detail, float $unitCost, float $totalCost): void
    {
        $detail->forceFill([
            'unit_cost' => $this->valuation->normalizeDecimal($unitCost),
            'total_cost' => $this->valuation->normalizeDecimal($totalCost),
        ])->save();
    }

    private function positiveQuantity(StockMovementDetail $detail): int
    {
        if ($detail->quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        return $detail->quantity;
    }

    private function ensureDraft(StockMovement $movement): void
    {
        if ($movement->status !== 'DRAFT') {
            throw new \Exception('Stock movement is already processed.');
        }
    }

    private function generateMovementNumber(string $prefix): string
    {
        return $prefix.now()->format('ymdHis').Str::upper(Str::random(4));
    }
}
