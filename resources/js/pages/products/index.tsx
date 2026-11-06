import { Head, Link, usePage } from '@inertiajs/react';
import { useAppearance } from '@/hooks/use-appearance';

type Product = {
    id: number;
    name: string;
    price: number | string;
    image: string | null;
    description: string;
    category: string | null;
};

interface PaginatedProducts {
    data: Product[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    products: PaginatedProducts;
    default_no_image: string;
}

export default function Products({ products, default_no_image }: Props) {
    const { resolvedAppearance } = useAppearance();
    const { settings } = usePage().props;
    const isDark = resolvedAppearance === 'dark';
    const primary_color = settings?.primary_color ?? '#4f46e5';
    const groupedProducts = products.data.reduce<Record<string, Product[]>>(
        (groups, product) => {
            const category = product.category?.trim() || 'Uncategorized';

            if (!groups[category]) {
                groups[category] = [];
            }

            groups[category].push(product);

            return groups;
        },
        {},
    );

    return (
        <>
            <Head title="Products">
                <meta name="description" content="KryzonTechSolutions - Browse and manage product catalog" />
            </Head>
            <main className={`min-h-screen p-4 sm:p-6 ${isDark ? 'bg-gray-950' : 'bg-gray-50'}`}>
            <div className="mx-auto max-w-7xl">
                {/* Page Header */}
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                            Explore Products
                        </h1>

                        <p className={`mt-1 text-sm ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                            Browse products by category
                        </p>
                    </div>

                    <span className={`rounded-full px-3 py-1 text-sm font-semibold ${isDark
                        ? `bg-[${primary_color}]/20 text-[${primary_color}]`
                        : `bg-[${primary_color}]/10 text-[${primary_color}]`
                        }`}>
                        {products.total} products
                    </span>
                </div>

                {/* Empty State */}
                {products.data.length === 0 ? (
                    <div className={`rounded-xl border ${isDark ? 'border-gray-700' : 'border-gray-200'} ${isDark ? 'bg-gray-900' : 'bg-white'} py-16 text-center`}>
                        <h2 className={`text-lg font-semibold ${isDark ? 'text-gray-100' : 'text-gray-800'}`}>
                            No products found
                        </h2>

                        <p className={`mt-2 text-sm ${isDark ? 'text-gray-500' : 'text-gray-500'}`}>
                            Products will appear here when available.
                        </p>
                    </div>
                ) : (
                    /* Category Sections */
                    <div className="space-y-10">
                        {Object.entries(groupedProducts).map(
                            ([category, categoryProducts]) => (
                                <section key={category}>
                                    {/* Category Header */}
                                    <div className={`mb-4 flex items-center justify-between pb-3 ${isDark ? 'border-gray-700' : 'border-gray-200'} border-b`}>
                                        <div className="flex items-center gap-3">
                                            <div>
                                                <h2 className={`text-lg font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                                                    {category}
                                                </h2>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Products Grid */}
                                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4 xl:grid-cols-5">
                                        {categoryProducts.map((product) => (
                                            <article
                                                key={product.id}
                                                className={`group min-w-0 overflow-hidden rounded-xl shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg ${isDark
                                                    ? 'border-gray-700 bg-gray-900'
                                                    : 'border-gray-200 bg-white'
                                                    }`}
                                            >
                                                {/* Product Image */}
                                                <div className="flex h-32 items-center justify-center overflow-hidden bg-gray-100 sm:h-44">
                                                    <img
                                                        src={
                                                            product.image ||
                                                            default_no_image
                                                        }
                                                        alt={product.name}
                                                        loading="lazy"
                                                        onError={(e) => {
                                                            const img =
                                                                e.currentTarget;

                                                            if (
                                                                img.dataset
                                                                    .fallback !==
                                                                'true'
                                                            ) {
                                                                img.dataset.fallback =
                                                                    'true';
                                                                img.src =
                                                                    default_no_image;
                                                            } else {
                                                                img.style.display =
                                                                    'none';
                                                            }
                                                        }}
                                                        className="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                                    />
                                                </div>

                                                {/* Product Details */}
                                                <div className="min-w-0 p-3 sm:p-4">
                                                    <h3 className={`text-sm font-semibold wrap-break-words ${isDark ? 'text-gray-100' : 'text-gray-900'}`}>
                                                        {product.name}
                                                    </h3>

                                                    <p className={`mt-2 text-lg font-bold ${isDark ? `text-[${primary_color}]` : `text-[${primary_color}]`}`}>
                                                        {Number(
                                                            product.price,
                                                        ).toLocaleString(
                                                            'en-IN',
                                                            {
                                                                style: 'currency',
                                                                currency: 'INR',
                                                                maximumFractionDigits: 2,
                                                            },
                                                        )}
                                                    </p>

                                                    <p className={`mt-3 line-clamp-3 text-xs leading-5 wrap-break-words ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                                                        {product.description?.trim() ||
                                                            'No description available.'}
                                                    </p>
                                                </div>
                                            </article>
                                        ))}
                                    </div>
                                </section>
                            ),
                        )}
                    </div>
                )}

                {/* Pagination */}
                {products.last_page > 1 && (
                    <nav
                        className="mt-8 flex items-center justify-center gap-2"
                        aria-label="Pagination"
                    >
                        {products.links.map((link) =>
                            link.url ? (
                                <Link
                                    key={link.label}
                                    href={link.url}
                                    className={`rounded-md px-3 py-1 text-sm font-medium transition-colors ${link.active
                                        ? `bg-[${primary_color}] text-white`
                                        : isDark
                                            ? 'text-gray-300 hover:bg-gray-800'
                                            : 'text-gray-700 hover:bg-gray-100'
                                        }`}
                                    aria-current={
                                        link.active ? 'page' : undefined
                                    }
                                >
                                    {link.label}
                                </Link>
                            ) : (
                                <span
                                    key={link.label}
                                    aria-disabled="true"
                                    className={`rounded-md px-3 py-1 text-sm font-medium ${isDark ? 'text-gray-600' : 'text-gray-400'}`}
                                >
                                    {link.label}
                                </span>
                            ),
                        )}
                    </nav>
                )}
            </div>
            </main>
        </>
    );
}
