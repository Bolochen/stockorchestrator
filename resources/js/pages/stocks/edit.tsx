import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { index, store } from '@/routes/stocks';

type Product = {
    id: number;
    sku: string;
    name: string;
    unit: string;
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
    product_id: number;
    warehouse_id: number;
    product: Product;
    warehouse: Warehouse;
};

type Props = {
    stock: Stock;
    defaultMovementNumber: string;
};

type AdjustmentForm = {
    post: boolean;
    movement: {
        movement_number: string;
        type: string;
        warehouse_id: number;
        reference_number: string;
    };
    details: Array<{
        product_id: number;
        quantity: string;
        unit_cost: string;
        notes: string;
    }>;
};

export default function StockEdit({ stock, defaultMovementNumber }: Props) {
    const { data, setData, post, processing, errors } = useForm<AdjustmentForm>(
        {
            post: true,
            movement: {
                movement_number: defaultMovementNumber,
                type: 'ADJUSTMENT',
                warehouse_id: stock.warehouse_id,
                reference_number: '',
            },
            details: [
                {
                    product_id: stock.product_id,
                    quantity: '1',
                    unit_cost: stock.average_cost,
                    notes: '',
                },
            ],
        },
    );

    function updateDetail(
        key: keyof AdjustmentForm['details'][number],
        value: string,
    ) {
        setData('details', [
            {
                ...data.details[0],
                [key]: value,
            },
        ]);
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        post(store().url);
    }

    return (
        <>
            <Head title={`Adjust ${stock.product.name}`} />

            <div className="p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Adjust Stock</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {stock.product.name} at {stock.warehouse.name}
                        </p>
                    </div>

                    <Button variant="outline" asChild>
                        <Link href={index()}>Back</Link>
                    </Button>
                </div>

                <div className="mt-6 grid gap-3 md:grid-cols-3">
                    <div className="rounded border p-4">
                        <div className="text-sm text-muted-foreground">
                            Current Quantity
                        </div>
                        <div className="mt-1 text-xl font-semibold">
                            {stock.quantity}
                        </div>
                    </div>
                    <div className="rounded border p-4">
                        <div className="text-sm text-muted-foreground">
                            Average Cost
                        </div>
                        <div className="mt-1 text-xl font-semibold">
                            {stock.average_cost}
                        </div>
                    </div>
                    <div className="rounded border p-4">
                        <div className="text-sm text-muted-foreground">
                            Warehouse
                        </div>
                        <div className="mt-1 text-xl font-semibold">
                            {stock.warehouse.code}
                        </div>
                    </div>
                </div>

                <form
                    onSubmit={submit}
                    className="mt-6 space-y-4 rounded border p-4"
                >
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label htmlFor="movement_number">
                                Movement Number
                            </Label>
                            <Input
                                id="movement_number"
                                value={data.movement.movement_number}
                                onChange={(event) =>
                                    setData('movement', {
                                        ...data.movement,
                                        movement_number: event.target.value,
                                    })
                                }
                            />
                            <InputError
                                message={errors['movement.movement_number']}
                            />
                        </div>

                        <div>
                            <Label htmlFor="reference_number">
                                Reference Number
                            </Label>
                            <Input
                                id="reference_number"
                                value={data.movement.reference_number}
                                onChange={(event) =>
                                    setData('movement', {
                                        ...data.movement,
                                        reference_number: event.target.value,
                                    })
                                }
                            />
                            <InputError
                                message={errors['movement.reference_number']}
                            />
                        </div>

                        <div>
                            <Label htmlFor="quantity">
                                Adjustment Quantity
                            </Label>
                            <Input
                                id="quantity"
                                type="number"
                                value={data.details[0].quantity}
                                onChange={(event) =>
                                    updateDetail('quantity', event.target.value)
                                }
                            />
                            <InputError
                                message={errors['details.0.quantity']}
                            />
                        </div>

                        <div>
                            <Label htmlFor="unit_cost">Unit Cost</Label>
                            <Input
                                id="unit_cost"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.details[0].unit_cost}
                                onChange={(event) =>
                                    updateDetail(
                                        'unit_cost',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={errors['details.0.unit_cost']}
                            />
                        </div>
                    </div>

                    <div>
                        <Label htmlFor="notes">Reason</Label>
                        <Input
                            id="notes"
                            value={data.details[0].notes}
                            onChange={(event) =>
                                updateDetail('notes', event.target.value)
                            }
                        />
                        <InputError message={errors['details.0.notes']} />
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={data.post}
                            onChange={(event) =>
                                setData('post', event.target.checked)
                            }
                        />
                        Post immediately
                    </label>

                    <Button type="submit" disabled={processing}>
                        {processing ? 'Saving...' : 'Save Adjustment'}
                    </Button>
                </form>
            </div>
        </>
    );
}
