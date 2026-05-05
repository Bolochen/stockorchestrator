import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store, index } from '@/routes/warehouses';

export default function WarehousesCreate() {
    return (
        <>
            <Head title="Create Warehouse" />

            <div className="p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Create Warehouse</h1>

                    <Button asChild>
                        <Link href={index()}>Back</Link>
                    </Button>
                </div>

                <Form {...store.form()} className="mt-6 space-y-4">
                    {({ errors, processing }) => (
                        <>
                            <div>
                                <Label htmlFor="code">Code</Label>
                                <Input type="text" id="code" name="code" />
                                <InputError message={errors.code} />
                            </div>

                            <div>
                                <Label htmlFor="name">Name</Label>
                                <Input type="text" id="name" name="name" />
                                <InputError message={errors.name} />
                            </div>

                            <div>
                                <Label htmlFor="location">Location</Label>
                                <Input
                                    type="text"
                                    id="location"
                                    name="location"
                                />
                                <InputError message={errors.location} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving...' : 'Save'}
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
