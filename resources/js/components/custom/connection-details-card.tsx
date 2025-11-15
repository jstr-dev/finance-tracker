import { Card, CardContent, CardHeader, CardTitle } from "../ui/card"

interface ConnectionDetailsCardProps {
    imageName: string;
    heading: string;
    children: React.ReactNode;
}

function ConnectionDetailsCard({ imageName, heading, children }: ConnectionDetailsCardProps)
{
    return <div className="flex flex-col gap-6">
        <div className="flex flex-row items-center gap-4">
            <img src={`/storage/assets/logos/${imageName}`} className="ml-2 h-8 w-8 rounded-md"></img>
            <h2 className="text-foreground font-medium">Settings</h2>
        </div>
        <div className="flex flex-col gap-2">
            <h2 className="text-foreground font-semibold">{heading}</h2>
            <div className="text-sm text-muted-foreground">{children}</div>
        </div>
    </div>
}

export default ConnectionDetailsCard
