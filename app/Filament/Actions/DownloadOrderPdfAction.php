<?php

namespace App\Filament\Actions;

use App\Enums\CustomIcon;
use App\Models\Order;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Contracts\Support\Htmlable;
use NumberFormatter;
use Spatie\LaravelPdf\Facades\Pdf;

class DownloadOrderPdfAction extends Action
{
    protected bool $forceDownload = true;

    public static function getDefaultName(): ?string
    {
        return 'downloadOrderPdf';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Download PDF')
            ->icon(fn (): string|Htmlable => FilamentIcon::resolve(CustomIcon::Pdf->value)
                    ?? CustomIcon::Pdf->value
            )
            ->color('danger')
            ->iconButton()
            ->action(function (Order $record) {
                $order = $record->loadMissing('items');
                $total = (float) $order->total_amount;
                $totalInCents = (int) round($total * 100);

                // Amount in words
                $amountInWords = null;

                if (class_exists(NumberFormatter::class)) {
                    $formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT);
                    $rupees = intdiv($totalInCents, 100);
                    $paise = $totalInCents % 100;
                    $rupeesInWords = $formatter->format($rupees);

                    if ($rupeesInWords !== false) {
                        $amountInWords = ucfirst($rupeesInWords)
                            .' rupees'
                            .($paise > 0
                                ? ' and '.$formatter->format($paise).' paise'
                                : '')
                            .' only';
                    }
                }

                // Logo
                $logoPath = null;
                $logoUrl = (new Setting)->lightLogoUrl();

                if ($logoUrl) {
                    $logoUrlPath = parse_url($logoUrl, PHP_URL_PATH);

                    if (is_string($logoUrlPath) && $logoUrlPath !== '') {
                        $publicDirectory = realpath(public_path());
                        $candidate = realpath(public_path(ltrim($logoUrlPath, '/')));

                        if ($publicDirectory !== false && $candidate !== false
                         && str_starts_with($candidate,$publicDirectory.DIRECTORY_SEPARATOR)
                         && is_file($candidate)
                         && is_readable($candidate)
                        ) {
                            $logoPath = $candidate;
                        }
                    }
                }

                // PDF data
                $data = [
                    'order' => $order,
                    'orderNo' => $order->order_number ?: $order->id,
                    'total' => $total,
                    'amountInWords' => $amountInWords,
                    'logoPath' => $logoPath,
                ];

                // Generate PDF
                $pdf = Pdf::view('pdf.order', $data)
                    ->format('a4')
                    ->generatePdfContent();

                // Download response
                $orderNumber = preg_replace(
                    '/[^A-Za-z0-9_-]/',
                    '-',
                    (string) $data['orderNo']
                );

                $filename = 'invoice-'.$orderNumber.'.pdf';

                return response()->stream(
                    static function () use ($pdf): void {
                        echo $pdf;
                    },
                    200,
                    [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => sprintf(
                            '%s; filename="%s"',
                            $this->forceDownload ? 'attachment' : 'inline',
                            $filename
                        ),
                        'Content-Length' => (string) strlen($pdf),
                        'X-Content-Type-Options' => 'nosniff',
                    ],
                );
            });
    }
}
