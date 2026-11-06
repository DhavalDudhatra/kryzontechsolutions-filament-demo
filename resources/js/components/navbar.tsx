import { Toggle } from '@/components/ui/toggle';
import { useAppearance } from '@/hooks/use-appearance';
import { Link, usePage } from '@inertiajs/react';
import { home } from '@/routes/index';
import { index as productsIndex } from '@/routes/products/index';
import { Menu, Moon, Sun, X } from 'lucide-react';
import { useState } from 'react';

export default function Navbar() {
    const { url, props } = usePage();
    const { logo, name } = props;
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const appName = name || import.meta.env.VITE_APP_NAME || 'KryzonTechSolutions';
    const lightLogo = logo?.light ?? '/logo-light.svg';
    const darkLogo = logo?.dark ?? '/logo-dark.svg';
    const currentPath = url.split('?')[0];
    const linkClass = (path: string) =>
        `transition-colors hover:text-blue-600 dark:hover:text-blue-400 ${
            currentPath === path
                ? 'font-semibold text-blue-600 dark:text-blue-400'
                : 'text-gray-700 dark:text-gray-200'
        }`;

    return (
        <header className="sticky top-0 z-30 border-b border-gray-200/90 bg-white/95 px-5 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-gray-950/95 sm:px-8">
            <div className="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-5">
                <Link
                    href={home.url()}
                    className="flex items-center"
                    aria-label={appName}
                    onClick={() => setIsMenuOpen(false)}
                >
                    <img
                        src={lightLogo}
                        alt={appName}
                        className="h-9 w-auto dark:hidden"
                    />
                    <img
                        src={darkLogo}
                        alt={appName}
                        className="hidden h-9 w-auto dark:block"
                    />
                </Link>

                <nav
                    aria-label="Main navigation"
                    className="hidden items-center gap-8 md:flex"
                >
                    <Link
                        href={home.url()}
                        aria-current={currentPath === '/' ? 'page' : undefined}
                        className={linkClass('/')}
                    >
                        Home
                    </Link>
                    <Link
                        href={productsIndex.url()}
                        aria-current={
                            currentPath === '/products' ? 'page' : undefined
                        }
                        className={linkClass('/products')}
                    >
                        Products
                    </Link>
                </nav>

                <div className="flex items-center gap-2">
                    <Toggle
                        aria-label="Toggle dark mode"
                        pressed={resolvedAppearance === 'dark'}
                        onPressedChange={() =>
                            updateAppearance(
                                resolvedAppearance === 'dark'
                                    ? 'light'
                                    : 'dark',
                            )
                        }
                    >
                        {resolvedAppearance === 'dark' ? (
                            <Sun aria-hidden="true" className="size-4" />
                        ) : (
                            <Moon aria-hidden="true" className="size-4" />
                        )}
                    </Toggle>
                    <button
                        type="button"
                        aria-label={isMenuOpen ? 'Close menu' : 'Open menu'}
                        aria-expanded={isMenuOpen}
                        aria-controls="mobile-navigation"
                        onClick={() => setIsMenuOpen((isOpen) => !isOpen)}
                        className="inline-flex size-10 items-center justify-center rounded-md text-gray-700 transition hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:text-gray-200 dark:hover:bg-gray-800 md:hidden"
                    >
                        {isMenuOpen ? (
                            <X aria-hidden="true" className="size-5" />
                        ) : (
                            <Menu aria-hidden="true" className="size-5" />
                        )}
                    </button>
                </div>
            </div>

            {isMenuOpen && (
                <nav
                    id="mobile-navigation"
                    aria-label="Mobile navigation"
                    className="mx-auto flex max-w-7xl flex-col gap-1 border-t border-gray-200 py-3 dark:border-gray-800 md:hidden"
                >
                    <Link
                        href={home.url()}
                        aria-current={currentPath === '/' ? 'page' : undefined}
                        onClick={() => setIsMenuOpen(false)}
                        className={`rounded-md px-3 py-2.5 text-sm ${linkClass('/')}`}
                    >
                        Home
                    </Link>
                    <Link
                        href={productsIndex.url()}
                        aria-current={
                            currentPath === '/products' ? 'page' : undefined
                        }
                        onClick={() => setIsMenuOpen(false)}
                        className={`rounded-md px-3 py-2.5 text-sm ${linkClass('/products')}`}
                    >
                        Products
                    </Link>
                </nav>
            )}
        </header>
    );
}
