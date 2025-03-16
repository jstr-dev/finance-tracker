import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, Connection } from '@/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Connections',
        href: '/connections',
    },
];

interface ConnectionsProps {
    connections: Connection[];
}

function ConnectionCard({ connection }: { connection: Connection }) {
    return (
        <Card className="py-0 hover:bg-muted hover:cursor-pointer h-full gap-3">
            <img src={connection.image} alt={'Image of ' + connection.name}
                className="h-32 w-full object-cover rounded-t-xl" />
            <CardHeader className="px-4 pb-4">
                <CardTitle className="text-sm">{connection.name}</CardTitle>
                <CardDescription className="text-xs">{connection.description}</CardDescription>
            </CardHeader>
        </Card>
    );
}

export default function Connections({ connections }: ConnectionsProps) {
    const [search, setSearch] = useState<string>('');

    // TODO: remove
    if (true) {
        const generateRandomConnections = (count: number): Connection[] => {
            const randomConnections: Connection[] = [];
            for (let i = 0; i < count; i++) {
                randomConnections.push({
                    id: 'id_' + i,
                    name: `Connection ${i + 1}`,
                    description: `Description for connection ${i + 1}`,
                    image: 'img.png',
                });
            }
            return randomConnections;
        };

        // Add 10 random connection objects
        connections = [...connections, ...generateRandomConnections(10)];
    }

    connections = connections.filter((connection: Connection) => connection.name.toLowerCase().includes(search.toLowerCase()));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Connections" />
            <div className="max-w-5xl mx-auto flex w-full flex-col gap-6 p-4 mt-2">
                <div className="w-full">
                    <Input
                        placeholder="Search connections..."
                        type="search"
                        className="w-64 max-sm:w-full"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>
                <div className="grid w-full gap-4 grid-cols-5 max-[1200px]:grid-cols-4 max-[1050px]:grid-cols-3 max-[850px]:grid-cols-2 max-[400px]:grid-cols-1 auto-rows-max">
                    {connections.map((connection: Connection) => (
                        <div key={connection.id}>
                            <ConnectionCard connection={connection} />
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
