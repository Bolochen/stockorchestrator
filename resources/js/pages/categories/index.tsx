import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { create, destroy, edit } from '@/routes/categories';

type Categories = {
    id: number;
    name: string;
};

type Props = {
    categories: Categories[];
};

function deleteCategory(id: number) {
    if (!confirm('Delete this category?')) {
        return;
    }

    router.delete(destroy(id).url);
}

export default function CategoryIndex({ categories }: Props) {
    return (
        <>
            <Head title="Categories" />
            <div className="p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Categories</h1>

                    <Button asChild>
                        <Link href={create()}>New Category</Link>
                    </Button>
                </div>

                <div className="mt-6 space-y-3">
                    {categories.map((category) => (
                        <div key={category.id} className="rounded border p-4">
                            <div className="font-medium">{category.name}</div>
                            <Button variant="outline" asChild>
                                <Link href={edit(category)}>Edit</Link>
                            </Button>
                            <Button
                                type="button"
                                variant="destructive"
                                onClick={() => deleteCategory(category.id)}
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
