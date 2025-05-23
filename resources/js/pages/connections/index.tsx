import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
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
    userConnections: string[];
}

function ConnectionCard({ connection, isActive}: { connection: Connection, isActive: boolean }) {
    const onClick = () => {
        router.visit('/connections/' + connection.id, {method: 'get'});
    }

    const onHover = () => {
        if (!connection.access) return;
        router.prefetch('/connections/' + connection.id, {method: 'get'}, {cacheFor: '1m'});
    }

    return (
        <Card className={cn("h-full p-4 gap-4", 
            isActive && 'border-green-200'
        )}>
            <CardHeader className="p-0">
                <div className='flex flex-row justify-between items-start'>
                    <img src={connection.image} className="h-8 w-8 rounded-md mb-2" />
                    {isActive && <Badge variant={"success"} className="h-6">Active</Badge>}
                </div>
                <CardTitle className="text-sm">{connection.name}</CardTitle>
                <CardDescription className="text-xs">{connection.description}</CardDescription>
            </CardHeader>
            <CardFooter className="p-0 w-full flex flex-row items-end h-full">
                <div className="w-full flex flex-row">
                    <Button className="w-16 text-xs hover:cursor-pointer" variant="outline" size="sm"
                        onClick={onClick} onMouseEnter={onHover}>{isActive ? 'Manage' : 'Connect'}</Button>
                </div>
            </CardFooter>
        </Card>
    );  
}

export default function Connections({ connections, userConnections }: ConnectionsProps) {
    const [search, setSearch] = useState<string>('');

    /**
     * TODO: make this faster. (cache active)
     */
    const isActiveConn = (connectionId: string) => {
        return userConnections.filter(uc => uc === connectionId).length > 0;
    }

    // TODO: remove
    if (false) {
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
    connections = connections.sort((a, b) => !isActiveConn(a.id) ? 1 : 0)

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
                            <ConnectionCard connection={connection} isActive={isActiveConn(connection.id)} />
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
