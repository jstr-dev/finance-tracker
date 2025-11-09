import Drawer from '@/components/drawer';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { BreadcrumbItem, Connection, UserConnection } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import Trading212 from './trading212';

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

function ConnectionCard({ connection, isActive, onClick }: { connection: Connection, isActive: boolean, onClick: () => void }) {
    const onHover = () => {
    }

    return (
        <Card className={cn("px-4 py-3 gap-4 border-[1.5px] shadow-none", 
            isActive && 'border-green-200'
        )}>
            <CardHeader className="p-0 flex-row flex items-center justify-between">
                <div className="flex-row flex gap-4 items-center">
                    <img src={connection.image} className="h-8 w-8 rounded-md" />
                    <div className="flex flex-col gap-0.5">
                        <CardTitle className="text-sm">{connection.name}</CardTitle>
                        <CardDescription className="text-xs">{connection.description}</CardDescription>
                    </div>
                </div>
                <Button className="w-16 text-xs hover:cursor-pointer" variant="outline" size="sm"
                    onClick={onClick} onMouseEnter={onHover}>{isActive ? 'Manage' : 'Connect'}</Button>
            </CardHeader>
        </Card>
    );  
}

export default function Connections({ connections, userConnections }: ConnectionsProps) {
    const [search, setSearch] = useState<string>('');
    const [drawerOpen, setDrawerOpen] = useState<boolean>(false);

    const isActiveConn = (connectionId: string) => {
        return userConnections.filter(uc => uc === connectionId).length > 0;
    }

    connections = connections.filter((connection: Connection) => connection.name.toLowerCase().includes(search.toLowerCase()));
    connections = connections.sort((a, b) => !isActiveConn(a.id) ? 1 : 0)

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Connections" />

            <div className="max-w-3xl mx-auto flex w-full flex-col gap-6 p-4 mt-2">
                <div className="w-full">
                    <Input
                        placeholder="Search connections..."
                        type="search"
                        className="w-64 max-sm:w-full bg-background"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>
                <div className="w-full gap-2 flex flex-col max-h-[80vh] overflow-y-auto">
                    {connections.map((connection: Connection) => (
                        <div key={connection.id}>
                            <ConnectionCard connection={connection} isActive={isActiveConn(connection.id)}
                                onClick={() => { setDrawerOpen(true) }} />
                        </div>
                    ))}
                </div>
            </div>

            <Drawer isOpen={drawerOpen} setIsOpen={setDrawerOpen}>
                <Trading212 connection={null} investments={undefined} errors={{}} />
            </Drawer>
        </AppLayout>
    );
}
