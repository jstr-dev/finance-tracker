interface ConnectionDetailsCardProps {
    imageName: string;
}

function ConnectionDrawerHeader({ imageName }: ConnectionDetailsCardProps)
{
    return <div className="flex flex-col gap-6">
        <div className="flex flex-row items-center gap-3">
            <img src={`/storage/assets/logos/${imageName}`} className="h-8 w-8 rounded-md"></img>
            <h2 className="text-foreground font-medium text-sm">Settings</h2>
        </div>
    </div>
}

export default ConnectionDrawerHeader
