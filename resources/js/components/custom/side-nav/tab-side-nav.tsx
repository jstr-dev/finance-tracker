import { cn } from "@/lib/utils"
import { ReactNode, useEffect, useState } from "react"

export interface Tab {
    key: string
    content: React.ReactNode
}

interface TabSideNavProps {
    tabs: Tab[]
    selectedTab: string
}

function TabButton()
{

}

export default function TabSideNav({tabs, selectedTab} : TabSideNavProps)
{
    const [activeTab, setActiveTab] = useState<ReactNode>();

    useEffect(() => {
        setActiveTab(tabs.find((tab) => tab.key === selectedTab)?.content);
    }, [tabs, selectedTab]);

    return <div className={cn('flex flex-row')}>
        <div className='flex-col'>

        </div>
        <div></div>
        <div className="tab-container"> 
            {activeTab}
        </div>
    </div>
}