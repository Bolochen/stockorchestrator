import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { create, destroy, edit } from '@/routes/products';

type Category = {
    id: number;
    name: string;
};

type Product = {
    id: number;
    sku: string;
    name: string;
    unit: string;
    minimum_stock: number;
    is_active: boolean;
    category: Category;
};

type Props = {
    products: Product[];
};

function deleteProduct(id: number) {
    if (!confirm('Delete this product?')) {
        return;
    }

    router.delete(destroy(id).url);
}

export default function ProductIndex({ products }: Props) {
    return (
        <>
            <Head title="Products" />
            <div className="p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Products</h1>

                    <Button asChild>
                        <Link href={create()}>New Product</Link>
                    </Button>
                </div>

                <div className="mt-6 space-y-3">
                    {products.map((product) => (
                        <div key={product.id} className="rounded border p-4">
                            <div className="font-medium">{product.name}</div>
                            <div className="text-sm text-muted-foreground">
                                {product.sku} - {product.category.name} -{' '}
                                {product.unit}
                            </div>
                            <div className="text-sm text-muted-foreground">
                                Minimum stock: {product.minimum_stock} -{' '}
                                {product.is_active ? 'Active' : 'Inactive'}
                            </div>
                            <Button variant="outline" asChild>
                                <Link href={edit(product)}>Edit</Link>
                            </Button>
                            <Button
                                type="button"
                                variant="destructive"
                                onClick={() => deleteProduct(product.id)}
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
