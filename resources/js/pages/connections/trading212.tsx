import ConnectionDetailsCard from '@/components/custom/connection-details-card';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SharedData, UserConnection, UserInvestment } from '@/types';
import {  router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ConnectionDrawerProps } from '.';
import DottedLine from '@/components/dottedline';

export default function Trading212({
    connections
}: ConnectionDrawerProps) {
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
        const errors = usePage<SharedData>().props.errors;

        useEffect(() => {
            if (errors.token) {
                setTokenError(errors.token);
            }
        }, [errors]);

        const onSubmit = (e: React.FormEvent) => {
            e.preventDefault();

            if (!token) {
                setTokenError('Please enter a token.');
                return;
            }

            router.post('/connections/trading212', { token: token });
        };

        return (
            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-2">
                    <div className="font-semibold">Add a new connection</div>
                    <div className="text-sm text-muted-foreground">Connect a new Trading212 account by entering your API Token below.</div>
                </div>
                <form className="flex flex-col gap-4" onSubmit={onSubmit}>
                    <div className="flex flex-col space-y-2">
                        <Label htmlFor="token">API Token</Label>
                        <Input name="token" placeholder="Enter your token..." value={token} onChange={(e) => setToken(e.target.value)} />
                        {tokenError && <p className="text-xs text-red-600">{tokenError}</p>}
                    </div>

                    <Button type="submit" className="hover:cursor-pointer">
                        Add Connection
                    </Button>
                </form>
            </div>
        );
    };

    const ActivePanel = () => (
        <>
            <Card className="gap-2 py-4 pb-8">
                <CardHeader className="px-4 pb-0">
                    <CardTitle className="text-sm">Your Account</CardTitle>
                </CardHeader>
                <CardDescription className="px-4">
                    {/* {connection?.metas.find((m) => m.key === 'initial_sync')?.value === 'false' ? (
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
                        </div>
                    )} */}
                </CardDescription>
            </Card>
        </>
    );

    return (
        <div className="flex flex-col w-full gap-6">
            <ConnectionDetailsCard imageName="trading212.png" heading="Trading212">
                Track your investments within the platform, creating a more accurate picture of your net worth.
            </ConnectionDetailsCard>
            <DottedLine />
            <InactivePanel />
            {/* {connection ? <ActivePanel /> : <InactivePanel />} */}
        </div>
    );
}
