import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { create, edit, destroy } from '@/routes/warehouses';

type Warehouse = {
    id: number;
    code: string;
    name: string;
    location: string;
};

type Props = {
    warehouses: Warehouse[];
};

function deleteWarehouse(id: number) {
    if (!confirm('Delete this warehouse?')) {
        return;
    }

    router.delete(destroy(id).url);
}

export default function WarehouseIndex({ warehouses }: Props) {
    return (
        <>
            <Head title="Warehouses" />
            <div className="p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Warehouses</h1>

                    <Button asChild>
                        <Link href={create()}>New warehouse</Link>
                    </Button>
                </div>

                <div className="mt-6 space-y-3">
                    {warehouses.map((warehouse) => (
                        <div key={warehouse.id} className="rounded border p-4">
                            <div className="font-medium">{warehouse.name}</div>
                            <div className="text-sm text-muted-foreground">
                                {warehouse.code} - {warehouse.location}
                            </div>
                            <Button variant="outline" asChild>
                                <Link href={edit(warehouse)}>Edit</Link>
                            </Button>
                            <Button
                                type="button"
                                variant="destructive"
                                onClick={() => deleteWarehouse(warehouse.id)}
                            >
                                Delete
                            </Button>
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}
