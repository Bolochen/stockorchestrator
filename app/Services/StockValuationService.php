<?php

namespace App\Services;

class StockValuationService
{
    public function calculateMovingAverage(
        int $oldQty,
        string $oldAverageCost,
        int $incomingQty,
        string $incomingUnitCost
    ): string {
        if ($oldQty < 0 || $incomingQty < 0) {
            throw new \InvalidArgumentException('Quantity cannot be negative.');
        }

        $newQty = $oldQty + $incomingQty;

        if ($newQty === 0) {
            return $this->normalizeDecimal(0);
        }

        $oldTotalValue = $oldQty * (float) $oldAverageCost;
        $incomingTotalValue = $incomingQty * (float) $incomingUnitCost;

        return $this->normalizeDecimal(($oldTotalValue + $incomingTotalValue) / $newQty);
    }

    public function calculateTotalValue(int $quantity, string $averageCost): string
    {
        return $this->normalizeDecimal($quantity * (float) $averageCost);
    }

    public function calculateSaleCost(int $quantity, string $averageCost): string
    {
        if ($quantity < 0) {
            throw new \InvalidArgumentException('Quantity cannot be negative.');
        }

        return $this->calculateTotalValue($quantity, $averageCost);
    }

    public function calculateLineTotal(int $quantity, string $unitCost): string
    {
        return $this->normalizeDecimal(abs($quantity) * (float) $unitCost);
    }

    public function normalizeDecimal(float|int|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
