import { usePage, Head } from '@inertiajs/react';
import { Suspense } from "react";

interface TabContentProps {
    tabKey: string;
    title?: string;
    meta?: {
        description?: string;
    };
    loader: () => React.ReactElement | null;
    fallback?: React.ReactNode;
}

export function TabContent({ tabKey, title, meta, loader, fallback }: TabContentProps) {
    // Set page title and meta for each tab
    return (
        <Suspense fallback={fallback}>
            <Head title={title ? `${title} | KryzonTechSolutions` : "KryzonTechSolutions"}>
                {meta && <meta name="description" content={meta.description} />}
            </Head>
            
            {/* Loader uses Inertia's deferred props for smooth transitions */}
            <div className="p-4">
                {loader()}
            </div>
        </Suspense>
    );
}