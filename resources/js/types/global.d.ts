import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare global {
    interface Window {
        __APP_NAME__?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            logo: {
                light: string;
                dark: string;
            };
            settings: {
                primary_color: string;
                default_no_image: string;
            };
            [key: string]: unknown;
        };
    }
}
