import ConnectionDetailsCard from '@/components/custom/connection-details-card';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SharedData, UserConnection, UserInvestment } from '@/types';
import {  router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { ConnectionDrawerProps } from '.';
import DottedLine from '@/components/dottedline';
import Loader from '@/components/loader';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CircleCheck } from 'lucide-react';

export default function Trading212({
    connections
}: ConnectionDrawerProps) {
    const { auth } = usePage<SharedData>().props;

    let connection: UserConnection | null = null;
    if (connections && connections.length > 0) {
        connection = connections[0];
    }

    useEffect(() => {
        window.Echo.private('user.' + auth.user.id).listen('Trading212SyncComplete', (data: any) => {
            router.reload({ only: ['connectionDrawerProps'] });
        });

        return () => {
            window.Echo.private('user.' + auth.user.id).stopListening('Trading212SyncComplete');
        };
    }, []);

    const InactivePanel = () => {
        const [token, setToken] = useState<string>('');
        const [tokenError, setTokenError] = useState<string>('');
        const errors = usePage<SharedData>().props.errors;

        useMemo(() => {
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

    const ActivePanel = ({ conn }: { conn: UserConnection }) => {
        return <div className="flex flex-col gap-6">
            {conn.status == 'pending' ? <Loader title="Fetching your data..." hint="We are pulling in all of your investments, please wait." /> :
                <div className="flex flex-col gap-2">
                    <div className="font-semibold">Your Account</div>
                    <div className="text-sm text-muted-foreground">Something here</div>
                </div>
            }
        </div>
    };

    return (
        <div className="flex flex-col w-full gap-6">
            <ConnectionDetailsCard imageName="trading212.png" heading="Trading212">
                Track your investments within the platform, creating a more accurate picture of your net worth.
            </ConnectionDetailsCard>
            <DottedLine />
            {connection ? <ActivePanel conn={connection} /> : <InactivePanel />}
        </div>
    );
}
