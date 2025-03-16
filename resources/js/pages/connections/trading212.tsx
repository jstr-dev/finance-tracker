import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Connections',
        href: '/connections',
    },

    {
        title: 'Trading212',
        href: '/connections/trading212',
    },
];

export default function Trading212() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Trading212" />
        </AppLayout>
    );
}
