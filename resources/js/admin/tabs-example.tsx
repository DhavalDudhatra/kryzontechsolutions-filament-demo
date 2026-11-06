import { AdminTabNavigation, AdminTabContent } from "@/components";
import { Head } from "@inertiajs/react";
import { Calendar, Settings, Shield } from "lucide-react";

interface TabContent {
    title: string;
    description?: string;
}

interface AdminTabsProps {
    initialTab: string;
}

export function AdminTabs({ initialTab }: AdminTabsProps) {
    const tabs = [
        { key: "dashboard", label: "Dashboard", icon: Shield },
        { key: "settings", label: "Settings", icon: Settings },
        { key: "products", label: "Products", icon: Calendar },
    ];

    const [activeTab, setActiveTab] = useState(initialTab);

    return (
        <div className="space-y-6">
            {/* Admin Tab Navigation - uses Inertia Link for SPA navigation */}
            <AdminTabNavigation
                tabs={tabs}
                initialTab={initialTab}
                onTabChange={setActiveTab}
                className="border-b border-slate-200 dark:border-zinc-800 mb-4"
            />

            {/* Admin Tab Content - smooth transitions with Suspense */}
            <AdminTabContent
                tabKey={activeTab}
                title={activeTab}
                loader={() => {
                    // Each tab loads its content lazily via Inertia
                    return (
                        <div className="space-y-4">
                            {/* Dashboard Tab */}
                            {activeTab === "dashboard" && (
                                <div>
                                    <Head title="Dashboard | Admin Panel" />
                                    <h2 className="text-2xl font-semibold">Admin Dashboard</h2>
                                    <p>Dashboard content for: {activeTab}</p>
                                    {/* Would render Filament dashboard widgets here */}
                                </div>
                            )}

                            {/* Settings Tab */}
                            {activeTab === "settings" && (
                                <div>
                                    <Head title="Settings | Admin Panel" />
                                    <h2 className="text-2xl font-semibold">Admin Settings</h2>
                                    <p>Settings content for: {activeTab}</p>
                                    {/* Would render Filament settings page here */}
                                </div>
                            )}

                            {/* Products Tab */}
                            {activeTab === "products" && (
                                <div>
                                    <Head title="Products | Admin Panel" />
                                    <h2 className="text-2xl font-semibold">Products Management</h2>
                                    <p>Products content for: {activeTab}</p>
                                    {/* Would render products management here */}
                                </div>
                            )}
                        </div>
                    );
                }}
                fallback={<div className="p-8 text-center dark:text-zinc-400">Loading admin section...</div>}
                className="min-h-[400px]"
            />
        </div>
    );
}