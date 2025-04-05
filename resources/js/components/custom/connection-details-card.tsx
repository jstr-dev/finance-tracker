import { Card, CardContent, CardHeader, CardTitle } from "../ui/card"

interface ConnectionDetailsCardProps {
    imageName: string;
    heading: string;
    children: React.ReactNode;
}

function ConnectionDetailsCard({ imageName, heading, children }: ConnectionDetailsCardProps)
{
    return <Card className="p-0 text-secondary">
        <CardHeader className="p-0 flex flex-row max-sm:flex-col">
            <CardTitle className="text-md flex flex-row items-center gap-4 w-80 bg-secondary-foreground rounded-l-md max-sm:rounded-t-md p-4 max-sm:w-full">
                <img src={`/storage/assets/logos/${imageName}`} className="ml-2 h-8 w-8 rounded-md" />
                <span>{heading}</span>
            </CardTitle>
            <CardContent className="rounded-r-2xl text-xs w-full text-primary p-4 flex flex-col gap-2">
                {children}
            </CardContent>
        </CardHeader>
    </Card>
}

export default ConnectionDetailsCard