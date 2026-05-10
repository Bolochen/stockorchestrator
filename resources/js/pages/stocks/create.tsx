import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { index, store } from '@/routes/stocks';
import { store as storeTransfer } from '@/routes/stocks/transfers';

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

type DetailForm = {
    product_id: string;
    quantity: string;
    unit_cost: string;
    notes: string;
};

type StockMovementForm = {
    post: boolean;
    source_warehouse_id: string;
    destination_warehouse_id: string;
    movement: {
        movement_number: string;
        type: string;
        warehouse_id: string;
        destination_warehouse_id: string;
        reference_number: string;
    };
    details: DetailForm[];
};

type Props = {
    warehouses: Warehouse[];
    products: Product[];
    movementTypes: string[];
    defaultMovementNumber: string;
};

const emptyDetail = (): DetailForm => ({
    product_id: '',
    quantity: '1',
    unit_cost: '0.00',
    notes: '',
});

export default function StockCreate({
    warehouses,
    products,
    movementTypes,
    defaultMovementNumber,
}: Props) {
    const { data, setData, post, processing, errors } =
        useForm<StockMovementForm>({
            post: true,
            source_warehouse_id: '',
            destination_warehouse_id: '',
            movement: {
                movement_number: defaultMovementNumber,
                type: 'RESTOCK',
                warehouse_id: '',
                destination_warehouse_id: '',
                reference_number: '',
            },
            details: [emptyDetail()],
        });
    const isTransfer = data.movement.type === 'TRANSFER';

    function updateMovement(
        key: keyof StockMovementForm['movement'],
        value: string,
    ) {
        setData('movement', {
            ...data.movement,
            [key]: value,
        });
    }

    function updateDetail(index: number, key: keyof DetailForm, value: string) {
        setData(
            'details',
            data.details.map((detail, detailIndex) =>
                detailIndex === index ? { ...detail, [key]: value } : detail,
            ),
        );
    }

    function addDetail() {
        setData('details', [...data.details, emptyDetail()]);
    }

    function removeDetail(index: number) {
        if (data.details.length === 1) {
            return;
        }

        setData(
            'details',
            data.details.filter((_, detailIndex) => detailIndex !== index),
        );
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        post(isTransfer ? storeTransfer().url : store().url);
    }

    return (
        <>
            <Head title="New Stock Movement" />

            <div className="p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            New Stock Movement
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create restock, sale, transfer, or adjustment
                            documents.
                        </p>
                    </div>

                    <Button variant="outline" asChild>
                        <Link href={index()}>Back</Link>
                    </Button>
                </div>

                <form onSubmit={submit} className="mt-6 space-y-6">
                    <div className="grid gap-4 rounded border p-4 md:grid-cols-2">
                        <div>
                            <Label htmlFor="movement_number">
                                Movement Number
                            </Label>
                            <Input
                                id="movement_number"
                                value={data.movement.movement_number}
                                onChange={(event) =>
                                    updateMovement(
                                        'movement_number',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={errors['movement.movement_number']}
                            />
                        </div>

                        <div>
                            <Label htmlFor="type">Type</Label>
                            <select
                                id="type"
                                value={data.movement.type}
                                onChange={(event) =>
                                    updateMovement('type', event.target.value)
                                }
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                {movementTypes.map((type) => (
                                    <option key={type} value={type}>
                                        {type}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors['movement.type']} />
                        </div>

                        <div>
                            <Label htmlFor="warehouse_id">
                                {isTransfer ? 'Source Warehouse' : 'Warehouse'}
                            </Label>
                            <select
                                id="warehouse_id"
                                value={data.movement.warehouse_id}
                                onChange={(event) =>
                                    updateMovement(
                                        'warehouse_id',
                                        event.target.value,
                                    )
                                }
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="">Select warehouse</option>
                                {warehouses.map((warehouse) => (
                                    <option
                                        key={warehouse.id}
                                        value={warehouse.id}
                                    >
                                        {warehouse.code} - {warehouse.name}
                                    </option>
                                ))}
                            </select>
                            <InputError
                                message={
                                    errors['movement.warehouse_id'] ||
                                    errors.source_warehouse_id
                                }
                            />
                        </div>

                        {isTransfer && (
                            <div>
                                <Label htmlFor="destination_warehouse_id">
                                    Destination Warehouse
                                </Label>
                                <select
                                    id="destination_warehouse_id"
                                    value={
                                        data.movement.destination_warehouse_id
                                    }
                                    onChange={(event) =>
                                        updateMovement(
                                            'destination_warehouse_id',
                                            event.target.value,
                                        )
                                    }
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="">Select destination</option>
                                    {warehouses.map((warehouse) => (
                                        <option
                                            key={warehouse.id}
                                            value={warehouse.id}
                                        >
                                            {warehouse.code} - {warehouse.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={
                                        errors[
                                            'movement.destination_warehouse_id'
                                        ] || errors.destination_warehouse_id
                                    }
                                />
                            </div>
                        )}

                        <div>
                            <Label htmlFor="reference_number">
                                Reference Number
                            </Label>
                            <Input
                                id="reference_number"
                                value={data.movement.reference_number}
                                onChange={(event) =>
                                    updateMovement(
                                        'reference_number',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={errors['movement.reference_number']}
                            />
                        </div>

                        {!isTransfer && (
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
                        )}
                    </div>

                    <div className="rounded border">
                        <div className="flex items-center justify-between border-b p-4">
                            <h2 className="font-semibold">Details</h2>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={addDetail}
                            >
                                <Plus className="mr-2 size-4" />
                                Add Line
                            </Button>
                        </div>

                        <div className="space-y-4 p-4">
                            {data.details.map((detail, detailIndex) => (
                                <div
                                    key={detailIndex}
                                    className={
                                        isTransfer
                                            ? 'grid gap-3 rounded border p-3 md:grid-cols-[1fr_120px_1fr_auto]'
                                            : 'grid gap-3 rounded border p-3 md:grid-cols-[1fr_120px_140px_1fr_auto]'
                                    }
                                >
                                    <div>
                                        <Label>Product</Label>
                                        <select
                                            value={detail.product_id}
                                            onChange={(event) =>
                                                updateDetail(
                                                    detailIndex,
                                                    'product_id',
                                                    event.target.value,
                                                )
                                            }
                                            className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                        >
                                            <option value="">
                                                Select product
                                            </option>
                                            {products.map((product) => (
                                                <option
                                                    key={product.id}
                                                    value={product.id}
                                                >
                                                    {product.sku} -{' '}
                                                    {product.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={
                                                errors[
                                                    `details.${detailIndex}.product_id`
                                                ]
                                            }
                                        />
                                    </div>

                                    <div>
                                        <Label>Quantity</Label>
                                        <Input
                                            type="number"
                                            value={detail.quantity}
                                            onChange={(event) =>
                                                updateDetail(
                                                    detailIndex,
                                                    'quantity',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                errors[
                                                    `details.${detailIndex}.quantity`
                                                ]
                                            }
                                        />
                                    </div>

                                    {!isTransfer && (
                                        <div>
                                            <Label>Unit Cost</Label>
                                            <Input
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                value={detail.unit_cost}
                                                onChange={(event) =>
                                                    updateDetail(
                                                        detailIndex,
                                                        'unit_cost',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `details.${detailIndex}.unit_cost`
                                                    ]
                                                }
                                            />
                                        </div>
                                    )}

                                    <div>
                                        <Label>Notes</Label>
                                        <Input
                                            value={detail.notes}
                                            onChange={(event) =>
                                                updateDetail(
                                                    detailIndex,
                                                    'notes',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                errors[
                                                    `details.${detailIndex}.notes`
                                                ]
                                            }
                                        />
                                    </div>

                                    <div className="flex items-end">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            onClick={() =>
                                                removeDetail(detailIndex)
                                            }
                                            disabled={data.details.length === 1}
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <Button type="submit" disabled={processing}>
                        {processing ? 'Saving...' : 'Save Movement'}
                    </Button>
                </form>
            </div>
        </>
    );
}
