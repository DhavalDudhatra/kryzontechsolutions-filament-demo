<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class ProductsImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;

    /** @var list<array{row: int, errors: array<int, string>}> */
    public array $failed = [];

    /**
     * Import products from an XLSX file.
     *
     * @return array{imported: int, failed: list<array{row: int, errors: array<int, string>}>}
     */
    public function import(string|UploadedFile $file): array
    {
        $path = $file instanceof UploadedFile ? $file->getPathname() : $file;
        $extension = $file instanceof UploadedFile
            ? $file->getClientOriginalExtension()
            : pathinfo($path, PATHINFO_EXTENSION);

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('The uploaded file could not be read.');
        }

        if (strtolower($extension) !== 'xlsx') {
            throw new RuntimeException('Please upload an Excel file with the .xlsx extension.');
        }

        Excel::import($this, $file, readerType: ExcelFormat::XLSX);

        return [
            'imported' => $this->imported,
            'failed' => $this->failed,
        ];
    }

    /**
     * Validate and import each product row.
     */
    public function collection(Collection $rows): void
    {
        $categories = Category::query()->get(['id', 'name']);
        $existingCategoryIds = $categories->pluck('id')->implode(', ');
        $categoriesByName = $categories->mapWithKeys(
            static fn (Category $category): array => [strtolower(trim($category->name)) => $category->id]
        );

        foreach ($rows as $index => $row) {
            $row = $row->toArray();

            if (count(array_filter($row, static fn (mixed $value): bool => filled($value))) === 0) {
                continue;
            }

            $status = strtolower(trim((string) ($row['is_active'] ?? $row['active'] ?? $row['status'] ?? '')));

            $isActive = match (true) {
                in_array($status, ['1', '1.0', 'true', 'yes', 'y', 'on', 'active'], true) => true,
                in_array($status, ['0', '0.0', 'false', 'no', 'n', 'off', 'inactive'], true) => false,
                default => true,
            };

            $categoryValue = $row['category_id'] ?? $row['category'] ?? null;
            $categoryId = filled($categoryValue) && ! is_numeric($categoryValue)
                ? $categoriesByName->get(strtolower(trim((string) $categoryValue)), $categoryValue)
                : $categoryValue;
            $product = [
                'name' => is_string($row['name'] ?? $row['product_name'] ?? null)
                    ? trim($row['name'] ?? $row['product_name'])
                    : ($row['name'] ?? $row['product_name'] ?? null),
                'price' => $row['price'] ?? null,
                'is_active' => $isActive,
                'category_id' => filled($categoryId) ? $categoryId : null,
                'image' => Product::normalizeImagePath(is_string($row['image'] ?? null) ? trim($row['image']) : null),
                'description' => is_string($row['description'] ?? null) ? trim($row['description']) : ($row['description'] ?? null),
            ];

            $validator = Validator::make($product, [
                'name' => ['required', 'string', 'max:255'],
                'price' => ['required', 'numeric', 'min:0'],
                'is_active' => ['boolean'],
                'category_id' => ['nullable', 'integer', 'exists:categories,id'],
                'image' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
            ], [
                'category_id.integer' => 'The category ":input" is not a valid category ID. Use the numeric ID of an existing category.',
                'category_id.exists' => $existingCategoryIds === ''
                    ? 'The category ID ":input" does not exist. No categories exist yet, create one first.'
                    : 'The category ID ":input" does not exist. Existing category IDs: '.$existingCategoryIds.'.',
            ]);

            if ($validator->fails()) {
                $this->failed[] = [
                    'row' => $index + 2,
                    'errors' => $validator->errors()->all(),
                ];

                continue;
            }

            if (is_string($product['image']) && Str::startsWith($product['image'], ['http://', 'https://'])) {
                $product['image'] = $this->downloadImage($product['image']);
            }

            Product::create($product);
            // created_by is set by model observer (auth admin)
            $this->imported++;
        }
    }

    /**
     * Download a remote image into public storage and return its stored path.
     */
    private function downloadImage(string $url): ?string
    {
        if (! $this->isSafeRemoteUrl($url)) {
            return null;
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(15)
                ->withoutRedirecting()
                ->get($url);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $contents = $response->body();
        $extension = $this->imageExtension((string) $response->header('Content-Type'));

        if ($extension === null || strlen($contents) > 5 * 1024 * 1024) {
            return null;
        }

        $path = 'products/'.Str::random(40).'-'.time().'.'.$extension;

        if (! Storage::disk('public')->put($path, $contents)) {
            throw new RuntimeException('Unable to store the imported product image.');
        }

        return $path;
    }

    /**
     * Reject URLs that target the local machine or a private network.
     */
    private function isSafeRemoteUrl(string $url): bool
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower(trim((string) parse_url($url, PHP_URL_HOST), '[]'));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        if (in_array($host, ['localhost', 'localhost.localdomain'], true)
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.internal')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            $records = dns_get_record($host, DNS_A | DNS_AAAA);

            if ($records === false || $records === []) {
                return false;
            }

            foreach ($records as $record) {
                $ip = $record['ip'] ?? $record['ipv6'] ?? null;

                if (! is_string($ip) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                    return false;
                }
            }

            return true;
        }

        return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    /**
     * Map a response content type to a safe image extension.
     */
    private function imageExtension(string $contentType): ?string
    {
        $mime = strtolower(trim(explode(';', $contentType)[0]));

        return [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
            'image/avif' => 'avif',
        ][$mime] ?? null;
    }
}
