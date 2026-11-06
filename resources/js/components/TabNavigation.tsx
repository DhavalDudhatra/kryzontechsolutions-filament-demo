import { Link } from '@inertiajs/react';
import { useState } from "react";

interface TabItem {
    key: string;
    label: string;
    href?: string; // Optional: for external links
}

interface TabNavigationProps {
    tabs: TabItem[];
    initialTab: string;
    onTabChange?: (tabKey: string) => void;
    className?: string;
}

export function TabNavigation({ tabs, initialTab, onTabChange, className }: TabNavigationProps) {
    const [activeTab, setActiveTab] = useState(initialTab);

    return (
        <div className={className || "border-b border-slate-200 dark:border-zinc-800"}>
            <div className="flex space-x-px">
                {tabs.map((tab) => (
                    <Link
                        key={tab.key}
                        href={tab.href || `/${tab.key}`}
                        className={`
                            text-sm font-medium text-slate-600 dark:text-zinc-400 
                            ${activeTab === tab.key ? 'text-slate-900 dark:text-white border-b-2 border-primary-600' : ''}
                            transition-colors duration-200
                        `}
                        onClick={() => setActiveTab(tab.key)}
                    >
                        {tab.label}
                    </Link>
                ))}
            </div>
        </div>
    );
}