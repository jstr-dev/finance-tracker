export default function Loader({title, hint}: {title?: string, hint?: string}) {
    return <div className="flex flex-col gap-1 justify-center items-center">
        {title && <div className="text-lg text-black">Fetching account information</div>}
        {hint && <div className="text-xs mb-4">This may take a while, grab a coffee!</div>}
        <span className="loader"></span>
    </div>
}