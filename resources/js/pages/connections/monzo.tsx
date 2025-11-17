import ConnectionDetailsCard from '@/components/custom/connection-drawer-header';
import Loader from '@/components/loader';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, UserConnection } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Connections',
        href: '/connections',
    },

    {
        title: 'Monzo',
        href: '/connections/monzo',
    },
];

interface TokenValidationErrors {
    token?: string;
}

export default function Monzo({ connection, errors }: { connection: UserConnection | null; errors: TokenValidationErrors }) {
    const InactivePanel = () => {
        const onSubmit = (e: React.FormEvent) => {
            e.preventDefault();
            router.post('/connections/monzo');
        };

        return (
            <>
                <Card className="py-4">
                    <CardHeader className="px-4 pb-0">
                        <CardTitle className="text-sm">Add a new connection</CardTitle>
                        <CardDescription className="text-xs">Connect to your monzo account to see your account details.</CardDescription>
                    </CardHeader>
                    <CardContent className="px-4 py-0">
                        <form className="flex flex-col gap-6" onSubmit={onSubmit}>
                            <Button type="submit" className="hover:cursor-pointer">
                                Connect to Your Monzo
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </>
        );
    };

    const ActivePanel = () => {
        return (
            <>
                <Card className="py-4 gap-2 pb-8">
                    <CardHeader className="px-4 pb-0">
                        <CardTitle className="text-sm">Your Account</CardTitle>
                    </CardHeader>
                    <CardDescription>
                        {connection?.metas.find((m) => m.key === 'initial_sync')?.value === 'false' && <Loader
                            title="Fetching account information"
                            hint="Approve the connection request on your Monzo account. This may take a while, grab a coffee!"
                        />}
                    </CardDescription>
                </Card>
            </>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Monzo" />
            <div className="container mx-auto flex w-full max-w-4xl flex-col gap-4 p-4">
                <ConnectionDetailsCard imageName="monzo.png" 
                heading="Monzo" >
                    <p>This connection allows you to connect your Monzo account with our platform.</p>
                    <p>
                    By connecting Monzo you will be able to see your current balance on your account.
                    </p>
                </ConnectionDetailsCard>
                {connection ? <ActivePanel /> : <InactivePanel />}
            </div>
        </AppLayout>
    );
}
