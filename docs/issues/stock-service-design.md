# Stock Service Design

## Goal

Design the application services needed for stock, stock movements, and stock movement details.

The main rule: controllers should not directly perform inventory business logic. Controllers should validate input, call a service, and return a response. The service should own the transaction, stock calculations, and movement posting rules.

## Recommendation

Use services first. Do not add repositories yet.

In this Laravel app, Eloquent already acts as a strong data access layer. A repository layer would mostly repeat `Stock::query()`, `StockMovement::create()`, and relationship calls without adding much value.

Add repositories later only if:

- the same complex query is reused in many places
- stock data starts coming from another source besides the database
- you need a clean boundary for heavy reporting queries
- tests become painful because service logic is tightly coupled to query details

For now, services can access models directly.

Example direction:

```php
StockMovementService
StockQueryService
StockValuationService
```

## Service Cases

### 1. Stock Query Service

Use this for read-only stock screens and reports.

Responsibilities:

- list current stock by warehouse
- list current stock by product
- find low-stock products
- show stock value by warehouse
- search stock by SKU, product name, or warehouse

Suggested methods:

```php
getWarehouseStock(int $warehouseId)
getProductStock(int $productId)
getLowStockItems(?int $warehouseId = null)
getStockSummaryByWarehouse(int $warehouseId)
```

This service can directly use `Stock::query()`.

### 2. Stock Movement Service

Use this for creating, posting, cancelling, and validating stock movement documents.

Responsibilities:

- create draft movement
- add movement detail lines
- post movement
- cancel movement
- prevent editing posted movements
- update stock quantity when movement is posted
- wrap posting logic in a database transaction

Suggested methods:

```php
createDraft(array $data): StockMovement
addDetail(StockMovement $movement, array $data): StockMovementDetail
post(StockMovement $movement): StockMovement
cancel(StockMovement $movement): StockMovement
```

Important rule:

Only `POSTED` movements should affect the `stocks` table. `DRAFT` movements are documents only.

### 3. Restock Case

When type is `RESTOCK`:

- increase stock quantity in the selected warehouse
- update average cost
- update total value
- store unit cost and total cost on movement detail

Example:

```text
Before:
quantity = 10
average_cost = 100
total_value = 1000

Restock:
quantity = 5
unit_cost = 120
total_cost = 600

After:
quantity = 15
total_value = 1600
average_cost = 106.67
```

### 4. Sale Case

When type is `SALE`:

- decrease stock quantity in the selected warehouse
- reject the sale if stock is not enough, unless negative stock is allowed
- use current average cost as the movement unit cost
- decrease total value based on average cost

Example:

```text
Before:
quantity = 10
average_cost = 100
total_value = 1000

Sale:
quantity = 3

After:
quantity = 7
total_value = 700
average_cost = 100
```

### 5. Transfer Case

A transfer can be modeled in two ways.

Recommended for this schema:

- create one `TRANSFER_OUT` from source warehouse
- create one linked `TRANSFER_IN` into destination warehouse

Why:

- each movement has one main `warehouse_id`
- the source and destination stock effects stay clear
- audit history is easier to read

Rules:

- `TRANSFER_OUT` decreases source warehouse stock
- `TRANSFER_IN` increases destination warehouse stock
- both should use the same cost basis
- both should be created in one transaction

Suggested method:

```php
transfer(int $sourceWarehouseId, int $destinationWarehouseId, array $details): array
```

Return both movements:

```php
[
    'out' => $transferOutMovement,
    'in' => $transferInMovement,
]
```

### 6. Adjustment Case

Use `ADJUSTMENT` for manual correction.

There are two valid designs:

- signed quantity: `+5` or `-3`
- unsigned quantity plus adjustment direction: `INCREASE` or `DECREASE`

Current migration uses `integer('quantity')`, so signed quantity is possible.

Rules:

- positive quantity increases stock
- negative quantity decreases stock
- require a reference number or note/reason
- do not silently adjust stock without audit detail

Potential future column:

```php
$table->text('notes')->nullable();
```

### 7. Stock Valuation Service

Use this if cost calculations start getting repeated.

Responsibilities:

- calculate moving average cost
- calculate total stock value
- calculate sale cost based on average cost
- normalize decimal precision

Suggested methods:

```php
calculateMovingAverage(int $oldQty, string $oldAverageCost, int $incomingQty, string $incomingUnitCost): string
calculateTotalValue(int $quantity, string $averageCost): string
```

This service is optional at first. If `StockMovementService` starts getting too large, extract valuation logic here.

## Direct Model Access vs Repository

### Access Models Directly In Services

Good:

```php
DB::transaction(function () use ($data) {
    $movement = StockMovement::create($data);
    $stock = Stock::where('warehouse_id', $warehouseId)
        ->where('product_id', $productId)
        ->lockForUpdate()
        ->firstOrCreate([...]);
});
```

This is okay because:

- it is idiomatic Laravel
- the domain is still small
- Eloquent relationships are readable
- fewer layers means easier learning and debugging

### Avoid This In Controllers

Bad:

```php
public function store(Request $request)
{
    StockMovement::create(...);
    Stock::where(...)->increment(...);
}
```

The controller should not know how stock posting works.

### Add Repository Later If Needed

Only add a repository when it removes real duplication or hides genuinely complex persistence.

Possible future repositories:

```php
StockRepository
StockMovementRepository
```

Example use case:

```php
$this->stocks->findForUpdate($warehouseId, $productId);
```

But do this later, not now.

## Suggested First Implementation Order

1. Create `StockMovementService`
2. Implement `createDraft`
3. Implement `addDetail`
4. Implement `post`
5. Add stock update logic for `RESTOCK`
6. Add stock update logic for `SALE`
7. Add transfer logic
8. Add adjustment logic
9. Add tests around posting behavior

## Testing Checklist

- restock creates stock when product does not exist in warehouse yet
- restock updates existing stock quantity and average cost
- sale decreases stock
- sale fails when stock is not enough
- transfer decreases source and increases destination
- adjustment can increase stock
- adjustment can decrease stock
- draft movement does not affect stock
- posted movement cannot be posted twice
- cancelled movement cannot be posted

## Mentor Note

Start simple: service plus Eloquent models.

The important boundary is not repository vs model. The important boundary is keeping inventory rules out of controllers and protecting stock updates with transactions. Once that is clean, the repository decision becomes much easier later.
