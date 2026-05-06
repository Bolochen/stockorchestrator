import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, update } from '@/routes/products';

type Category = {
    id: number;
    name: string;
};

type Product = {
    id: number;
    sku: string;
    name: string;
    category_id: number;
    unit: string;
    minimum_stock: number;
    is_active: boolean;
};

type Props = {
    product: Product;
    categories: Category[];
};

export default function ProductEdit({ product, categories }: Props) {
    return (
        <>
            <Head title="Edit Product" />

            <div className="p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Edit Product</h1>

                    <Button asChild>
                        <Link href={index()}>Back</Link>
                    </Button>
                </div>

                <Form {...update.form(product)} className="mt-6 space-y-4">
                    {({ errors, processing }) => (
                        <>
                            <div>
                                <Label htmlFor="sku">SKU</Label>
                                <Input
                                    type="text"
                                    id="sku"
                                    name="sku"
                                    defaultValue={product.sku}
                                />
                                <InputError message={errors.sku} />
                            </div>

                            <div>
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    type="text"
                                    id="name"
                                    name="name"
                                    defaultValue={product.name}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div>
                                <Label htmlFor="category_id">Category</Label>
                                <select
                                    id="category_id"
                                    name="category_id"
                                    defaultValue={product.category_id}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
                                >
                                    {categories.map((category) => (
                                        <option
                                            key={category.id}
                                            value={category.id}
                                        >
                                            {category.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.category_id} />
                            </div>

                            <div>
                                <Label htmlFor="unit">Unit</Label>
                                <Input
                                    type="text"
                                    id="unit"
                                    name="unit"
                                    defaultValue={product.unit}
                                />
                                <InputError message={errors.unit} />
                            </div>

                            <div>
                                <Label htmlFor="minimum_stock">
                                    Minimum Stock
                                </Label>
                                <Input
                                    type="number"
                                    id="minimum_stock"
                                    name="minimum_stock"
                                    defaultValue={product.minimum_stock}
                                    min={0}
                                />
                                <InputError message={errors.minimum_stock} />
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    type="hidden"
                                    name="is_active"
                                    value="0"
                                />
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    name="is_active"
                                    value="1"
                                    defaultChecked={product.is_active}
                                />
                                <Label htmlFor="is_active">Active</Label>
                                <InputError message={errors.is_active} />
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
