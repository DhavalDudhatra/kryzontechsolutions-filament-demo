<?php

namespace App\Filament\Exports;

use App\Models\Product;
use App\Support\DateFormats;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductExporter extends Exporter
{
    protected static ?string $model = Product::class;

    /*
     * Filament's built-in export (the queued "Export" action).
     * These are the columns it will write.
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label('Product Name'),
            ExportColumn::make('category.id')->label('Category Id'),
            ExportColumn::make('category.name')->label('Category'),
            ExportColumn::make('price'),
            ExportColumn::make('stock_quantity')->label('Stock'),
            ExportColumn::make('is_active')
                ->label('Status')
                ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive'),
        ];
    }

    /*
     * Notification shown when Filament's export finishes.
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your product export has completed and '.Str::of('row')->counted($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Str::of('row')->counted($failedRowsCount).' failed to export.';
        }

        return $body;
    }

    /*
     * Direct Excel download (use this from a button).
     * It writes the file row by row.
     */
    public static function download(Builder $query, string $fileName = 'products.xlsx'): StreamedResponse
    {
        return response()->streamDownload(function () use ($query): void {

            // Step 1: set the width of each column.
            $options = new Options;
            $widths = [7, 30, 20, 12, 10, 12, 60, 50, 16, 60]; // 10 widths matching header row

            foreach ($widths as $index => $width) {
                $options->setColumnWidth($width, $index + 1);
            }

            // Step 2: open the Excel file (it streams straight to the browser).
            $writer = new Writer($options);
            $writer->openToFile('php://output');

            // Step 3: write the header row (bold white text on a dark background).
            $headerStyle = (new Style)
                ->setFontBold()
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor(Color::toARGB('1F2937'));

            $writer->addRow(Row::fromValues([
                'No.',
                'Product Name',
                'Category ID',
                'Category',
                'Price',
                'Stock',
                'Status',
                'Description',
                'Image',
                'Created At',
            ], $headerStyle));

            // Step 4: write one row per product, numbering them 1, 2, 3...
            $number = 1;

            foreach ($query->with('category')->lazyById(1000) as $product) {
                $writer->addRow(Row::fromValues([
                    $number,
                    $product->name,
                    $product->category?->id,
                    $product->category?->name,
                    $product->price,
                    $product->stock_quantity,
                    $product->is_active ? 'Active' : 'Inactive',
                    strip_tags((string) $product->description),
                    $product->image ? asset('storage/'.$product->image) : null, // ensure consistent disk usage
                    $product->created_at?->format(DateFormats::date()),
                ]));

                $number++;
            }

            // Step 5: finish the file.
            $writer->close();
        }, $fileName);
    }
}
