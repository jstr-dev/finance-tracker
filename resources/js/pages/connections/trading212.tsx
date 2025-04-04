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
        title: 'Trading212',
        href: '/connections/trading212',
    },
];

/*
    TODO: Add this to a individual component so we can reuse it on other connections.
*/
function ConnectionDetailsCard()
{
    return <Card className="p-0 text-secondary">
        <CardHeader className="p-0 flex flex-row max-sm:flex-col">
            <CardTitle className="text-md flex flex-row items-center gap-4 w-80 bg-secondary-foreground rounded-l-md max-sm:rounded-t-md p-4 max-sm:w-full">
                <img src="/storage/assets/logos/trading212.png" className="ml-2 h-8 w-8 rounded-md" />
                <span>Trading 212</span>
            </CardTitle>
            <CardContent className="rounded-r-2xl text-xs w-full text-primary p-4 flex flex-col gap-2">
                <p>This connection allows you to connect your Trading 212 account with our platform.</p>
                <p>
                    By connecting Trading 212 you will be able to see your investments within our platform and use them to track a more accurate picture of your net worth
                </p>
            </CardContent>
        </CardHeader>
    </Card>
}

interface TokenValidationErrors {
    token?: string;
}

export default function Trading212({ connection, errors }: { connection: UserConnection | null; errors: TokenValidationErrors }) {
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

    const ActivePanel = () => {
        return (
            <>
                <Card className="py-4 gap-2 pb-8">
                    <CardHeader className="px-4 pb-0">
                        <CardTitle className="text-sm">Your Account</CardTitle>
                    </CardHeader>
                    <CardDescription>
                        {connection?.metas.find((m) => m.key === 'initial_sync')?.value === 'false' && <div className="flex flex-col gap-1 justify-center items-center">
                            <div className="text-lg text-black">Fetching account information</div>
                            <div className="text-xs mb-4">This may take a while, grab a coffee!</div>
                            <span className="loader"></span>
                        </div>}
                    </CardDescription>
                </Card>
            </>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Trading212" />
            <div className="container mx-auto flex w-full max-w-4xl flex-col gap-4 p-4">
                <ConnectionDetailsCard />
                {connection ? <ActivePanel /> : <InactivePanel />}
            </div>
        </AppLayout>
    );
}
