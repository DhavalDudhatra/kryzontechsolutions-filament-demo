import { TabNavigation, TabContent } from "@/components";
import { index as productsIndex } from "@/routes/products/index";
import { ArrowDown } from "lucide-react";

interface Product {
    id: number;
    name: string;
    price: number;
    category: string;
}

interface TabsProps {
    category?: string;
}

export function ProductsTabs({ category }: TabsProps) {
    const tabs = [
        { key: "all", label: "All Products" },
        { key: "category", label: "By Category" },
        { key: "featured", label: "Featured" },
    ];

    const [activeTab, setActiveTab] = useState("all");

    return (
        <div className="space-y-4">
            {/* Tab Navigation - uses Inertia Link for SPA navigation */}
            <TabNavigation
                tabs={tabs}
                initialTab="all"
                onTabChange={setActiveTab}
                className="mb-4 border-b border-slate-200 dark:border-zinc-800"
            />

            {/* Tab Content - lazy loads via Inertia */}
            <TabContent
                tabKey={activeTab}
                title={activeTab === "all" ? "Products" : activeTab === "category" ? "Category Products" : "Featured Products"}
                meta={{
                    description: activeTab === "all" ? "View all products" : "Browse products by category"
                }}
                loader={() => {
                    // Use Inertia's built-in data fetching with preserveState
                    return (
                        <div className="space-y-4">
                            {/* Example: Show different content based on active tab */}
                            {activeTab === "all" && (
                                <div>
                                    <h2 className="text-xl font-semibold">All Products</h2>
                                    {/* Products grid would go here */}
                                    <p>Products content for tab: {activeTab}</p>
                                </div>
                            )}

                            {activeTab === "category" && (
                                <div>
                                    <h2 className="text-xl font-semibold">Products by Category</h2>
                                    <p>Category filtering content</p>
                                    {/* Would use useForm with preserveState for category filter */}
                                </div>
                            )}

                            {activeTab === "featured" && (
                                <div>
                                    <h2 className="text-xl font-semibold">Featured Products</h2>
                                    <p>Featured products content</p>
                                </div>
                            )}
                        </div>
                    );
                }}
                fallback={<div className="p-8 text-center dark:text-zinc-400">Loading tab content...</div>}
            />
        </div>
    );
}