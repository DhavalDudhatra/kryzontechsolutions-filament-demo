import { Head, Link } from '@inertiajs/react';
import { index as productsIndex } from '@/routes/products/index';
import { ArrowRight, ArrowUpRight, PackageOpen } from 'lucide-react';

type Product = {
    id: number;
    name: string;
    price: number | string;
    image: string | null;
    description: string;
    category: string | null;
};

interface Props {
    products: Product[];
    default_no_image: string;
}

const formatPrice = (price: number | string): string =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2,
    }).format(Number(price));

function ProductImage({
    product,
    fallback,
    className,
}: {
    product: Product;
    fallback: string;
    className: string;
}) {
    return (
        <img
            src={product.image || fallback}
            alt={product.name}
            loading="lazy"
            onError={(event) => {
                const image = event.currentTarget;

                if (image.dataset.fallback !== 'true') {
                    image.dataset.fallback = 'true';
                    image.src = fallback;
                } else {
                    image.style.visibility = 'hidden';
                }
            }}
            className={className}
        />
    );
}

export default function Home({ products, default_no_image }: Props) {
    const featuredProduct = products[0];

    return (
        <>
            <Head title="Home">
                <meta name="description" content="KryzonTechSolutions - Browse products and manage your business" />
            </Head>
            <main className="overflow-hidden bg-white text-slate-950 dark:bg-zinc-950 dark:text-zinc-50">
            <section className="relative isolate border-b border-slate-200/80 dark:border-zinc-800">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_75%_35%,rgba(59,130,246,0.12),transparent_38%),linear-gradient(to_bottom,#f8fafc,white)] dark:bg-[radial-gradient(ellipse_at_75%_35%,rgba(59,130,246,0.16),transparent_38%),linear-gradient(to_bottom,#18181b,#09090b)]"
                />
                <div className="mx-auto grid max-w-7xl items-center gap-12 px-5 py-16 sm:px-8 sm:py-20 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16 lg:px-10 lg:py-24">
                    <div className="max-w-2xl">
                        <p className="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3.5 py-1.5 text-xs font-semibold tracking-wide text-blue-800 dark:border-blue-900 dark:bg-blue-950/60 dark:text-blue-200">
                            <span
                                aria-hidden="true"
                                className="size-1.5 rounded-full bg-blue-600 dark:bg-blue-400"
                            />
                            KRYZONTECHSOLUTIONS
                        </p>
                        <h1 className="mt-7 max-w-xl text-4xl font-semibold leading-[1.08] tracking-tight sm:text-5xl lg:text-6xl">
                            Explore the products that fit your world.
                        </h1>
                        <p className="mt-6 max-w-lg text-base leading-7 text-slate-600 dark:text-zinc-300 sm:text-lg sm:leading-8">
                            Browse the current KryzonTechSolutions collection,
                            with product details and pricing all in one place.
                        </p>
                        <div className="mt-9 flex flex-col gap-3 sm:flex-row">
                            <Link
                                href={productsIndex.url()}
                                className="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-blue-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:bg-blue-500 dark:text-slate-950 dark:hover:bg-blue-400"
                            >
                                Browse products
                                <ArrowRight aria-hidden="true" className="size-4" />
                            </Link>
                            <a
                                href="#latest-products"
                                className="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white/70 px-5 py-3 text-sm font-semibold text-slate-800 transition hover:border-slate-400 hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:border-zinc-700 dark:bg-zinc-900/70 dark:text-zinc-100 dark:hover:border-zinc-600 dark:hover:bg-zinc-900"
                            >
                                See recent additions
                                <ArrowUpRight aria-hidden="true" className="size-4" />
                            </a>
                        </div>
                        <p className="mt-5 text-xs leading-5 text-slate-500 dark:text-zinc-400">
                            Availability and product information are shown on
                            each listing.
                        </p>
                    </div>

                    <div className="relative mx-auto w-full max-w-xl">
                        <div
                            aria-hidden="true"
                            className="absolute -inset-5 rounded-4xl bg-blue-100/70 blur-2xl dark:bg-blue-950/50"
                        />
                        <div className="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-3 shadow-xl shadow-slate-900/10 dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-black/30">
                            <div className="relative flex aspect-[1.2/1] items-center justify-center overflow-hidden rounded-2xl bg-slate-100 dark:bg-zinc-800">
                                {featuredProduct ? (
                                    <ProductImage
                                        product={featuredProduct}
                                        fallback={default_no_image}
                                        className="size-full object-contain p-8 sm:p-12"
                                    />
                                ) : (
                                    <div className="flex flex-col items-center gap-4 text-slate-400 dark:text-zinc-500">
                                        <PackageOpen
                                            aria-hidden="true"
                                            className="size-16 stroke-[1.2]"
                                        />
                                        <span className="text-sm font-medium">
                                            Product collection
                                        </span>
                                    </div>
                                )}
                                <span className="absolute left-4 top-4 rounded-full border border-white/80 bg-white/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-600 shadow-sm backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/90 dark:text-zinc-300">
                                    Product collection
                                </span>
                            </div>
                            {featuredProduct && (
                                <div className="flex items-center justify-between gap-4 px-3 pb-2 pt-4 sm:px-4">
                                    <div className="min-w-0">
                                        {featuredProduct.category && (
                                            <p className="truncate text-xs font-medium text-blue-700 dark:text-blue-300">
                                                {featuredProduct.category}
                                            </p>
                                        )}
                                        <p className="mt-1 truncate text-base font-semibold">
                                            {featuredProduct.name}
                                        </p>
                                    </div>
                                    <p className="shrink-0 text-sm font-semibold tabular-nums text-slate-700 dark:text-zinc-200">
                                        {formatPrice(featuredProduct.price)}
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </section>

            <section
                id="latest-products"
                aria-labelledby="latest-products-title"
                className="mx-auto max-w-7xl scroll-mt-8 px-5 py-16 sm:px-8 sm:py-20 lg:px-10"
            >
                <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-300">
                            The collection
                        </p>
                        <h2
                            id="latest-products-title"
                            className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl"
                        >
                            Recent additions
                        </h2>
                        <p className="mt-3 max-w-xl text-sm leading-6 text-slate-600 dark:text-zinc-400">
                            A look at the latest products in the catalog.
                        </p>
                    </div>
                    <Link
                        href={productsIndex.url()}
                        className="inline-flex w-fit items-center gap-2 rounded-md py-2 text-sm font-semibold text-blue-700 transition hover:text-blue-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600 dark:text-blue-300 dark:hover:text-blue-200"
                    >
                        View all products
                        <ArrowRight aria-hidden="true" className="size-4" />
                    </Link>
                </div>

                {products.length > 0 ? (
                    <div className="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        {products.map((product) => (
                            <article
                                key={product.id}
                                className="group min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white transition duration-200 hover:-translate-y-1 hover:border-slate-300 hover:shadow-lg hover:shadow-slate-900/5 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700 dark:hover:shadow-black/20"
                            >
                                <div className="flex aspect-4/3 items-center justify-center overflow-hidden bg-slate-100 dark:bg-zinc-800">
                                    <ProductImage
                                        product={product}
                                        fallback={default_no_image}
                                        className="size-full object-contain p-5 transition-transform duration-300 group-hover:scale-[1.04]"
                                    />
                                </div>
                                <div className="p-5">
                                    {product.category && (
                                        <p className="truncate text-xs font-medium text-blue-700 dark:text-blue-300">
                                            {product.category}
                                        </p>
                                    )}
                                    <h3 className="mt-2 line-clamp-2 min-h-12 text-base font-semibold leading-6">
                                        {product.name}
                                    </h3>
                                    <p className="mt-2 text-sm font-semibold tabular-nums text-slate-800 dark:text-zinc-100">
                                        {formatPrice(product.price)}
                                    </p>
                                    {product.description.trim() && (
                                        <p className="mt-3 line-clamp-2 text-sm leading-6 text-slate-600 dark:text-zinc-400">
                                            {product.description}
                                        </p>
                                    )}
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="mt-8 flex flex-col items-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center dark:border-zinc-700 dark:bg-zinc-900/60">
                        <PackageOpen
                            aria-hidden="true"
                            className="size-9 text-slate-400 dark:text-zinc-500"
                        />
                        <h3 className="mt-4 text-base font-semibold">
                            No products are available right now
                        </h3>
                        <p className="mt-2 max-w-md text-sm leading-6 text-slate-600 dark:text-zinc-400">
                            Check the full catalog again later for current
                            product listings.
                        </p>
                    </div>
                )}
            </section>

            <section className="border-y border-slate-200 bg-slate-50 dark:border-zinc-800 dark:bg-zinc-900/60">
                <div className="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-12 sm:px-8 md:flex-row md:items-center md:justify-between lg:px-10">
                    <div className="max-w-2xl">
                        <h2 className="text-2xl font-semibold tracking-tight">
                            Ready to explore the full collection?
                        </h2>
                        <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-zinc-400">
                            Browse all currently listed products and their
                            details.
                        </p>
                    </div>
                    <Link
                        href={productsIndex.url()}
                        className="inline-flex min-h-11 w-fit items-center justify-center gap-2 rounded-lg bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-700 dark:bg-white dark:text-slate-950 dark:hover:bg-zinc-200"
                    >
                        Go to products
                        <ArrowRight aria-hidden="true" className="size-4" />
                    </Link>
                </div>
            </section>

            <footer className="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-8 text-sm sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10">
                <p className="font-semibold tracking-tight">
                    KryzonTechSolutions
                </p>
                <p className="text-slate-500 dark:text-zinc-400">
                    Product information is subject to change.
                </p>
            </footer>
            </main>
        </>
    );
}
