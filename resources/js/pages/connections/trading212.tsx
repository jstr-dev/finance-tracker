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
        const [errorState, setErrorState] = useState<{
            keyId?: string;
            secretKeyId?: string;
        }>();
        const errors = usePage<SharedData>().props.errors;

        useMemo(() => {
            setErrorState({
                keyId: errors.key_id,
                secretKeyId: errors.secret_key_id
            });
        }, [errors]);

        const onSubmit = (e: React.FormEvent) => {
            e.preventDefault();

            const formData = new FormData(e.currentTarget as HTMLFormElement);
            const keyId = formData.get('key_id');
            const secretKeyId = formData.get('secret_key_id');

            setErrorState({});

            if (!keyId) {
                setErrorState(prev => ({ ...prev, keyId: 'API Key ID is required' }));
            }

            if (!secretKeyId) {
                setErrorState(prev => ({ ...prev, secretKeyId: 'Secret Key ID is required' }));
            }

            if (!keyId || !secretKeyId) {
                return;
            }

            router.post('/connections/trading212', {
                key_id: keyId,
                secret_key: secretKeyId
            }); 
        };

        return (
            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-1">
                    <div className="font-medium text-sm">Add a new connection</div>
                    <div className="text-sm text-muted-foreground">Connect a new Trading212 account by entering your API credentials below.</div>
                </div>
                <form className="flex flex-col gap-4" onSubmit={onSubmit}>
                    <div className="flex flex-col gap-3">
                        <Input name="key_id" placeholder="API Key ID" className="text-xs" />
                        {errorState?.keyId && <p className="text-xs text-red-600">{errorState.keyId}</p>}
                        <Input name="secret_key_id" placeholder="Secret Key ID" className='text-xs' />
                        {errorState?.secretKeyId && <p className="text-xs text-red-600">{errorState.secretKeyId}</p>}
                    </div>

                    <Button type="submit" className="hover:cursor-pointer">
                        Connect
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
