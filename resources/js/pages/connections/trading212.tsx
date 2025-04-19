import ConnectionDetailsCard from '@/components/custom/connection-details-card';
import Loader from '@/components/loader';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, SharedData, UserConnection, UserInvestment } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { AlertCircle, CircleCheck, CircleCheckBig } from 'lucide-react';
import { useEffect, useState } from 'react';

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

interface TokenValidationErrors {
    token?: string;
}

export default function Trading212({
    connection,
    investments,
    errors,
}: {
    connection: UserConnection | null;
    errors: TokenValidationErrors;
    investments?: UserInvestment[]; //TODO: type
}) {
    const { auth } = usePage<SharedData>().props;

    useEffect(() => {
        window.Echo.private('user.' + auth.user.id).listen('Trading212SyncComplete', (data: any) => {
            router.visit('/connections/trading212', { method: 'get' });
        });

        return () => {
            window.Echo.private('user.' + auth.user.id).stopListening('Trading212SyncComplete');
        };
    }, []);

    const InactivePanel = () => {
        const [token, setToken] = useState<string>('');
        const [tokenError, setTokenError] = useState<string>('');

        const onSubmit = (e: React.FormEvent) => {
            e.preventDefault();

            if (!token) {
                setTokenError('Please enter a token.');
                return;
            }

            router.post('/connections/trading212', { token: token });
        };

        useEffect(() => {
            if (errors.token) {
                setTokenError(errors.token);
            }
        }, []);

        return (
            <>
                <Card className="py-4">
                    <CardHeader className="px-4 pb-0">
                        <CardTitle className="text-sm">Add a new connection</CardTitle>
                        <CardDescription className="text-xs">Connect a new Trading212 account by entering your API Token below.</CardDescription>
                    </CardHeader>
                    <CardContent className="px-4 py-0">
                        <form className="flex flex-col gap-6" onSubmit={onSubmit}>
                            <div className="flex flex-col space-y-2">
                                <Label htmlFor="token">API Token</Label>
                                <Input name="token" placeholder="Enter your token..." value={token} onChange={(e) => setToken(e.target.value)} />
                                {tokenError && <p className="text-xs text-red-600">{tokenError}</p>}
                            </div>

                            <Button type="submit" className="hover:cursor-pointer">
                                Add Connection
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </>
        );
    };

    const ActivePanel = () => (
        <>
            <Card className="gap-2 py-4 pb-8">
                <CardHeader className="px-4 pb-0">
                    <CardTitle className="text-sm">Your Account</CardTitle>
                </CardHeader>
                <CardDescription className="px-4">
                    {connection?.metas.find((m) => m.key === 'initial_sync')?.value === 'false' ? (
                        <Loader title="Fetching account information" hint="This may take a while, grab a coffee!" />
                    ) : (
                        <div className="flex flex-col gap-2">
                            <Alert variant="success" className="flex h-full flex-row items-center mt-2 mb-2 ">
                                <CircleCheck className={'size-6! translate-y-0!'} />
                                <div>
                                    <AlertTitle>Connection Active</AlertTitle>
                                    <AlertDescription>Your Trading212 account is successfully linked.</AlertDescription>
                                </div>
                            </Alert>

                            {investments?.map((investment) => (
                                <p key={investment.id}>
                                    <span className="font-semibold">{investment.name}</span> - {investment.current_value} {investment.currency}
                                </p>
                            ))}
                        </div>
                    )}
                </CardDescription>
            </Card>
        </>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Trading212" />
            <div className="container mx-auto flex w-full max-w-4xl flex-col gap-4 p-4">
                <ConnectionDetailsCard imageName="trading212.png" heading="Trading212">
                    <p>This connection allows you to connect your Trading 212 account with our platform.</p>
                    <p>
                        By connecting Trading 212 you will be able to see your investments within our platform and use them to track a more accurate
                        picture of your net worth
                    </p>
                </ConnectionDetailsCard>
                {connection ? <ActivePanel /> : <InactivePanel />}
            </div>
        </AppLayout>
    );
}
