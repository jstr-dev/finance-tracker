import Drawer from '@/components/drawer';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { BreadcrumbItem, Connection } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useEffect, useState, useRef } from 'react';
import Trading212 from './trading212';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Connections', href: '/connections' },
];

export interface ConnectionDrawerProps {
    type?: string;
    connections?: any[];
}

interface ConnectionsProps {
    connections: Connection[];
    userConnections: string[];
    connectionDrawerProps?: ConnectionDrawerProps | null;
}

const ConnectionDrawerComponents: Record<string, React.ComponentType<any>> = {
    trading212: Trading212,
};

function ConnectionCard({
    connection,
    isActive,
    onClick,
}: {
    connection: Connection;
    isActive: boolean;
    onClick: () => void;
}) {
    return (
        <Card
            className={cn(
                'px-4 py-3 gap-4 border-[1.5px] shadow-none transition-colors',
                isActive && 'border-green-200'
            )}
        >
            <CardHeader className="p-0 flex-row flex items-center justify-between">
                <div className="flex-row flex gap-4 items-center">
                    <img src={connection.image} className="h-8 w-8 rounded-md" />
                    <div className="flex flex-col gap-0.5">
                        <CardTitle className="text-sm">{connection.name}</CardTitle>
                        <CardDescription className="text-xs">{connection.description}</CardDescription>
                    </div>
                </div>
                <Button
                    className="w-16 text-xs"
                    variant="outline"
                    size="sm"
                    onClick={onClick}
                >
                    {isActive ? 'Manage' : 'Connect'}
                </Button>
            </CardHeader>
        </Card>
    );
}

export default function Connections({
    connections: initialConnections,
    userConnections,
    connectionDrawerProps,
}: ConnectionsProps) {
    const [search, setSearch] = useState<string>('');
    const [drawerOpen, setDrawerOpen] = useState<boolean>(false);

    useEffect(() => {
        if (connectionDrawerProps) {
            setDrawerOpen(true);
        }
    }, [connectionDrawerProps]);

    const isActiveConn = (connectionId: string) =>
        userConnections.includes(connectionId);

    const visitConnection = (connection: Connection) => {
        router.visit(route('connections.index', { connection: connection.id }), {
            only: ['connectionDrawerProps'],
            preserveScroll: true,
            preserveState: true,
        });
    };

    const handleCloseDrawer = () => {
        setDrawerOpen(false);

        router.visit(route('connections.index'), {
            only: ['connectionDrawerProps'],
            preserveScroll: true,
            preserveState: true,
        });
    };

    const connections = initialConnections
        .filter((connection) =>
            connection.name.toLowerCase().includes(search.toLowerCase())
        )
        .sort((a, b) => (isActiveConn(a.id) ? -1 : 1));

    const DrawerComponent = connectionDrawerProps?.type
        ? ConnectionDrawerComponents[connectionDrawerProps.type]
        : null;

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
                    {connections.map((connection) => (
                        <div key={connection.id}>
                            <ConnectionCard
                                connection={connection}
                                isActive={isActiveConn(connection.id)}
                                onClick={() => visitConnection(connection)}
                            />
                        </div>
                    ))}
                </div>
            </div>

            <Drawer isOpen={drawerOpen} setIsOpen={handleCloseDrawer}>
                {DrawerComponent ? (
                    <DrawerComponent {...connectionDrawerProps} />
                ) : (
                    <div className="p-4 text-sm text-muted-foreground">
                        No connection component found.
                    </div>
                )}
            </Drawer>
        </AppLayout>
    );
}
