import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SharedData, UserConnection } from '@/types';
import {  router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { ConnectionDrawerProps } from '.';
import Loader from '@/components/loader';
import ConnectionDrawerHeader from '@/components/custom/connection-drawer-header';
import { DrawerFooter } from '@/components/drawer';
import DottedLine from '@/components/dottedline';

export default function Trading212({
    connections
}: ConnectionDrawerProps) {
    const { auth } = usePage<SharedData>().props;
    const [needsChanges, setNeedsChanges] = useState<boolean>(false);
    const [connectionDeleting, setConnectionDeleting] = useState<boolean>(false);

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
                <div className="flex flex-col gap-1">
                    <div className="font-medium text-sm">Add a new connection</div>
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
        const tokenStr = '*'.repeat(conn.token_length) + (conn.last_4_of_token || '????');

        return <div className="flex flex-col gap-6">
            {conn.status == 'pending' ? <Loader title="Fetching your data..." hint="We are pulling in all of your investments, please wait." /> :
                <div className="flex flex-col gap-6">
                    <div className="flex flex-col gap-2">
                        <div className="font-medium text-sm">Your Account</div>
                        <div className="text-sm text-muted-foreground">You can manage your account settings here.</div>
                    </div>

                    <div className="flex flex-col gap-2">
                        <div className="text-sm font-medium">Token</div>
                        <Input value={tokenStr} readOnly disabled />
                    </div>
                </div>
            }
        </div>
    };

    return (
        <div className="flex flex-col w-full justify-between h-full">
            <div className="flex flex-col w-full gap-6">
                <ConnectionDrawerHeader imageName="trading212.png" />
                {connection ? <ActivePanel conn={connection} /> : <InactivePanel />}
            </div>

            <DrawerFooter className={"gap-6"}>
                <DottedLine />
                <div className="flex flex-row-reverse gap-2">
                    <Button variant={"outline"} size="sm" disabled={!connection || !needsChanges}>Save changes</Button>
                    <Button variant={"destructive"} size="sm" disabled={!connection || connectionDeleting || connection.status != 'active'} onClick={() => {
                        if (connection) {
                            setConnectionDeleting(true);
                            router.delete('/connections/trading212/' + connection.id, {
                                onSuccess: () => {
                                    setConnectionDeleting(false);
                                    router.reload({ only: ['connectionDrawerProps'] });
                                }
                            });
                        }
                    }}>{connectionDeleting ? 'Disconnecting...' : 'Disconnect'}</Button>
                </div>
            </DrawerFooter>
        </div>
    );
}
