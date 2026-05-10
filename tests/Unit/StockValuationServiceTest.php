<?php

use App\Services\StockValuationService;

test('it calculates moving average cost', function () {
    $service = new StockValuationService;

    $averageCost = $service->calculateMovingAverage(10, '100.00', 5, '120.00');

    expect($averageCost)->toBe('106.67');
});

test('it calculates total value', function () {
    $service = new StockValuationService;

    expect($service->calculateTotalValue(7, '100.00'))->toBe('700.00');
});

test('it calculates sale cost from average cost', function () {
    $service = new StockValuationService;

    expect($service->calculateSaleCost(3, '100.00'))->toBe('300.00');
});

test('it normalizes decimal precision', function () {
    $service = new StockValuationService;

    expect($service->normalizeDecimal('12.5'))->toBe('12.50');
});
