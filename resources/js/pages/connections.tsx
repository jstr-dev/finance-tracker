import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem, Connection } from "@/types";
import { Head } from "@inertiajs/react";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Connections',
        href: '/connections',
    },
];

interface ConnectionsProps
{
    connections: Connection[]
}

export default function Connections({connections}: ConnectionsProps)
{
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Connections" />
            <div className="container mx-auto p-4">
                {connections.map(connection => (
                    <div key={connection.id}>

                    </div>
                ))}
            </div>
        </AppLayout>
    )
}
