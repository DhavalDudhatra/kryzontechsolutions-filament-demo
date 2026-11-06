import { Link } from '@inertiajs/react';
import { useState } from "react";

interface AdminTabItem {
    key: string;
    label: string;
    icon?: string; // lucide-react icon component or name
}

interface AdminTabNavigationProps {
    tabs: AdminTabItem[];
    initialTab: string;
    onTabChange?: (tabKey: string) => void;
    className?: string;
    preserveState?: boolean; // Preserve form state between tab switches
}

export function AdminTabNavigation({
    tabs,
    initialTab,
    onTabChange,
    className,
    preserveState = false,
}: AdminTabNavigationProps) {
    const [activeTab, setActiveTab] = useState(initialTab);

    return (
        <div className={className || "border-b border-slate-200 dark:border-zinc-800 mb-4"}>
            <div className="flex space-x-2">
                {tabs.map((tab) => (
                    <Link
                        key={tab.key}
                        href={tab.key}
                        className={`
                            inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium 
                            text-slate-600 dark:text-zinc-400 transition-colors 
                            ${activeTab === tab.key 
                                ? 'bg-primary-100 text-primary-700 shadow-sm' 
                                : 'hover:bg-gray-100 hover:text-slate-900 dark:hover:bg-white/6 dark:hover:text-white'}
                        `}
                        onClick={() => setActiveTab(tab.key)}
                    >
                        {tab.icon && <i className={`${tab.icon} mr-1 h-4 w-4`} aria-hidden="true" />}
                        {tab.label}
                    </Link>
                ))}
            </div>
        </div>
    );
}