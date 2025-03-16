import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, Connection, UserConnection } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Connections',
        href: '/connections',
    },
];

interface ConnectionsProps {
    connections: Connection[];
    userConnections: UserConnection[];
}

function ConnectionCard({ connection, userConnections }: { connection: Connection, userConnections: UserConnection[] }) {
    const onClick = () => {
        router.visit('/connections/' + connection.id, {method: 'get'});
    }

    const onHover = () => {
        if (!connection.access) return;
        router.prefetch('/connections/' + connection.id, {method: 'get'}, {cacheFor: '1m'});
    }

    const isActiveConn = (connectionId: string) => {
        return userConnections.filter(uc => uc.user_id === connectionId).length > 0;
    }

    return (
        <Card className="h-full p-4 gap-4">
            <CardHeader className="p-0">
                <img src={connection.image}
                    className="h-8 w-8 rounded-md mb-2" />
                <CardTitle className="text-sm">{connection.name}</CardTitle>
                <CardDescription className="text-xs">{connection.description}</CardDescription>
            </CardHeader>
            <CardFooter className="p-0 w-full flex flex-row items-end h-full">
                <div className="w-full flex flex-row">
                    <Button className="w-16 text-xs hover:cursor-pointer" variant="outline" size="sm"
                        onClick={onClick} onMouseEnter={onHover}>{isActiveConn(connection.id) ? 'Manage' : 'Connect'}</Button>
                </div>
            </CardFooter>
        </Card>
    );  
}

export default function Connections({ connections, userConnections }: ConnectionsProps) {
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
                        className="w-64 max-sm:w-full bg-background"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>
                <div className="grid w-full gap-4 grid-cols-3 max-[1200px]:grid-cols-3 max-[1050px]:grid-cols-2 max-[600px]:grid-cols-1 auto-rows-max">
                    {connections.map((connection: Connection) => (
                        <div key={connection.id}>
                            <ConnectionCard connection={connection} userConnections={userConnections} />
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
