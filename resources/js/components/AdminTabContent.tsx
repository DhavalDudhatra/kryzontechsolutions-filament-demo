import { usePage, Head } from '@inertiajs/react';
import { Suspense } from "react";

interface AdminTabContentProps {
    tabKey: string;
    title?: string;
    meta?: {
        description?: string;
    };
    loader: () => React.ReactNode | null;
    fallback?: React.ReactNode;
    className?: string;
}

export function AdminTabContent({
    tabKey,
    title,
    meta,
    loader,
    fallback,
    className,
}: AdminTabContentProps) {
    // Set page title and meta for each admin tab
    return (
        <Suspense fallback={fallback}>
            <Head title={title ? `${title} | Admin Panel` : "Admin Panel"}>
                {meta && <meta name="description" content={meta.description} />}
            </Head>
            
            <div className={className || "p-6"}>
                {loader()}
            </div>
        </Suspense>
    );
}