import Navbar from '@/components/navbar';
import { Head } from "@inertiajs/react";

interface AppLayoutProps {
    children: React.ReactNode;
    title?: string;
    description?: string;
}

export default function AppLayout({ children, title, description }: AppLayoutProps) {
    return (
        <>
            <Navbar />
            <Head>
                <title>{title ? `${title} - KryzonTechSolutions` : 'KryzonTechSolutions'}</title>
                <meta name="description" content={description || 'KryzonTechSolutions - Professional admin panel and website'} />
                <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                <meta name="theme-color" content="#4f46e5" />
            </Head>
            {children}
        </>
    );
}
