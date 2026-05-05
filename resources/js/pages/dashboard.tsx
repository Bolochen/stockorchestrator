import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';

type DashboardProps = {
    productCount: number;
};

export default function Dashboard({ productCount }: DashboardProps) {
    return (
        <>
            <Head title="Dashboard" />
            <div>{productCount}</div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
