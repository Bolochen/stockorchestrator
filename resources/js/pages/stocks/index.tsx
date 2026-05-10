import { Head, Link, router } from '@inertiajs/react';
import { Filter, PackageSearch, Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { create, edit, index, show } from '@/routes/stocks';

type Product = {
    id: number;
    sku: string;
    name: string;
};

type Warehouse = {
    id: number;
    code: string;
    name: string;
};

type Stock = {
    id: number;
    quantity: number;
    average_cost: string;
    total_value: string;
    product: Product;
    warehouse: Warehouse;
};

type Summary = {
    warehouse_id: number;
    total_items: number;
    total_quantity: number;
    total_value: string;
} | null;

type Filters = {
    warehouse_id: number | null;
    product_id: number | null;
    low_stock: boolean;
};

type Props = {
    stocks: Stock[];
    warehouses: Warehouse[];
    products: Product[];
    filters: Filters;
    summary: Summary;
};

const money = (value: string) =>
    new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value));

export default function StockIndex({
    stocks,
    warehouses,
    products,
    filters,
    summary,
}: Props) {
    function applyFilters(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const form = new FormData(event.currentTarget);

        router.get(
            index().url,
            {
                warehouse_id: form.get('warehouse_id') || undefined,
                product_id: form.get('product_id') || undefined,
                low_stock: form.get('low_stock') ? 1 : undefined,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    }

    return (
        <>
            <Head title="Stock" />

            <div className="p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Stock</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Current on-hand quantities and inventory value.
                        </p>
                    </div>

                    <Button asChild>
                        <Link href={create()}>
                            <Plus className="mr-2 size-4" />
                            New Movement
                        </Link>
                    </Button>
                </div>

                <form
                    onSubmit={applyFilters}
                    className="mt-6 grid gap-3 rounded border p-4 md:grid-cols-[1fr_1fr_auto_auto]"
                >
                    <select
                        name="warehouse_id"
                        defaultValue={filters.warehouse_id ?? ''}
                        className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="">All warehouses</option>
                        {warehouses.map((warehouse) => (
                            <option key={warehouse.id} value={warehouse.id}>
                                {warehouse.code} - {warehouse.name}
                            </option>
                        ))}
                    </select>

                    <select
                        name="product_id"
                        defaultValue={filters.product_id ?? ''}
                        className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="">All products</option>
                        {products.map((product) => (
                            <option key={product.id} value={product.id}>
                                {product.sku} - {product.name}
                            </option>
                        ))}
                    </select>

                    <label className="flex h-10 items-center gap-2 rounded-md border px-3 text-sm">
                        <input
                            type="checkbox"
                            name="low_stock"
                            defaultChecked={filters.low_stock}
                        />
                        Low stock
                    </label>

                    <Button type="submit" variant="outline">
                        <Filter className="mr-2 size-4" />
                        Apply
                    </Button>
                </form>

                {summary && (
                    <div className="mt-4 grid gap-3 md:grid-cols-3">
                        <div className="rounded border p-4">
                            <div className="text-sm text-muted-foreground">
                                Items
                            </div>
                            <div className="mt-1 text-xl font-semibold">
                                {summary.total_items}
                            </div>
                        </div>
                        <div className="rounded border p-4">
                            <div className="text-sm text-muted-foreground">
                                Quantity
                            </div>
                            <div className="mt-1 text-xl font-semibold">
                                {summary.total_quantity}
                            </div>
                        </div>
                        <div className="rounded border p-4">
                            <div className="text-sm text-muted-foreground">
                                Value
                            </div>
                            <div className="mt-1 text-xl font-semibold">
                                {money(summary.total_value)}
                            </div>
                        </div>
                    </div>
                )}

                <div className="mt-6 overflow-hidden rounded border">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-left">
                            <tr>
                                <th className="p-3 font-medium">Product</th>
                                <th className="p-3 font-medium">Warehouse</th>
                                <th className="p-3 text-right font-medium">
                                    Qty
                                </th>
                                <th className="p-3 text-right font-medium">
                                    Avg Cost
                                </th>
                                <th className="p-3 text-right font-medium">
                                    Value
                                </th>
                                <th className="p-3 text-right font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {stocks.map((stock) => (
                                <tr key={stock.id} className="border-b">
                                    <td className="p-3">
                                        <div className="font-medium">
                                            {stock.product.name}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {stock.product.sku}
                                        </div>
                                    </td>
                                    <td className="p-3">
                                        <div>{stock.warehouse.name}</div>
                                        <div className="text-xs text-muted-foreground">
                                            {stock.warehouse.code}
                                        </div>
                                    </td>
                                    <td className="p-3 text-right">
                                        {stock.quantity}
                                    </td>
                                    <td className="p-3 text-right">
                                        {money(stock.average_cost)}
                                    </td>
                                    <td className="p-3 text-right">
                                        {money(stock.total_value)}
                                    </td>
                                    <td className="p-3">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={show(stock)}>
                                                    View
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={edit(stock)}>
                                                    Adjust
                                                </Link>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {stocks.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="p-8 text-center text-muted-foreground"
                                    >
                                        <PackageSearch className="mx-auto mb-2 size-6" />
                                        No stock rows found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
