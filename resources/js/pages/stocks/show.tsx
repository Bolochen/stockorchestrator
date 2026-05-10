import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { edit, index } from '@/routes/stocks';

type Product = {
    sku: string;
    name: string;
    unit: string;
};

type Warehouse = {
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

type Props = {
    stock: Stock;
    valuation: {
        total_value: string;
    };
};

const money = (value: string) =>
    new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value));

export default function StockShow({ stock, valuation }: Props) {
    return (
        <>
            <Head title={`${stock.product.name} Stock`} />

            <div className="p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {stock.product.name}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {stock.product.sku} in {stock.warehouse.name}
                        </p>
                    </div>

                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={index()}>Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={edit(stock)}>Adjust</Link>
                        </Button>
                    </div>
                </div>

                <div className="mt-6 grid gap-3 md:grid-cols-4">
                    <div className="rounded border p-4">
                        <div className="text-sm text-muted-foreground">
                            Quantity
                        </div>
                        <div className="mt-1 text-2xl font-semibold">
                            {stock.quantity}
                        </div>
                    </div>
                    <div className="rounded border p-4">
                        <div className="text-sm text-muted-foreground">
                            Unit
                        </div>
                        <div className="mt-1 text-2xl font-semibold">
                            {stock.product.unit}
                        </div>
                    </div>
                    <div className="rounded border p-4">
                        <div className="text-sm text-muted-foreground">
                            Average Cost
                        </div>
                        <div className="mt-1 text-2xl font-semibold">
                            {money(stock.average_cost)}
                        </div>
                    </div>
                    <div className="rounded border p-4">
                        <div className="text-sm text-muted-foreground">
                            Stock Value
                        </div>
                        <div className="mt-1 text-2xl font-semibold">
                            {money(valuation.total_value)}
                        </div>
                    </div>
                </div>

                <div className="mt-6 rounded border p-4">
                    <h2 className="font-semibold">Location</h2>
                    <div className="mt-3 grid gap-3 text-sm md:grid-cols-2">
                        <div>
                            <div className="text-muted-foreground">
                                Warehouse
                            </div>
                            <div className="font-medium">
                                {stock.warehouse.name}
                            </div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">Code</div>
                            <div className="font-medium">
                                {stock.warehouse.code}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
