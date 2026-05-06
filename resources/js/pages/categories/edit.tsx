import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update, index } from '@/routes/categories';

type Category = {
    id: number;
    name: string;
};

type Props = {
    category: Category;
};

export default function CategoriesCreate({ category }: Props) {
    return (
        <>
            <Head title="Edit Category" />

            <div className="p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Edit Category</h1>

                    <Button asChild>
                        <Link href={index()}>Back</Link>
                    </Button>
                </div>

                <Form {...update.form(category)} className="mt-6 space-y-4">
                    {({ errors, processing }) => (
                        <>
                            <div>
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    type="text"
                                    id="name"
                                    name="name"
                                    defaultValue={category.name}
                                />
                                <InputError message={errors.name} />
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
