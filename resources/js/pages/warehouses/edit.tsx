import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update, index } from '@/routes/warehouses';

type Warehouse = {
    id: number;
    code: string;
    name: string;
    location: string;
};

type Props = {
    warehouse: Warehouse;
};

export default function WarehouseEdit({ warehouse }: Props) {
    return (
        <>
            <Head title="Edit Warehouse" />

            <div className="p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Edit Warehouse</h1>

                    <Button asChild>
                        <Link href={index()}>Back</Link>
                    </Button>
                </div>

                <Form {...update.form(warehouse)} className="mt-6 space-y-4">
                    {({ errors, processing }) => (
                        <>
                            <div>
                                <Label htmlFor="code">Code</Label>
                                <Input
                                    id="code"
                                    name="code"
                                    defaultValue={warehouse.code}
                                />
                                <InputError message={errors.code} />
                            </div>

                            <div>
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={warehouse.name}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div>
                                <Label htmlFor="location">Location</Label>
                                <Input
                                    id="location"
                                    name="location"
                                    defaultValue={warehouse.location}
                                />
                                <InputError message={errors.location} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing ? 'Updating...' : 'Update'}
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
