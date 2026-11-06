<?php

namespace App\Models;

use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Stringable;

/**
 * @property string $template_key
 * @property string $template_name
 * @property string $email_subject
 * @property string $email_body
 * @property bool $is_active
 * @property list<string>|null $cc
 * @property list<string>|null $bcc
 * @property int|string|null $created_by
 */
class EmailTemplate extends Model
{
    protected $fillable = ['template_key', 'template_name', 'email_subject', 'email_body', 'cc', 'bcc', 'is_active'];

    protected $casts = [
        'cc' => 'array',
        'bcc' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (EmailTemplate $template): void {
            // Record the acting admin, only when someone is actually authenticated.
            $template->created_by = $template->created_by ?? (filled($authId = Auth::id()) ? (int) $authId : null);
        });
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return array<string, string> */
    public static function getMergeTags(): array
    {
        return [
            'user.name' => 'User Name',
            'user.email' => 'User Email',
            'customer.name' => 'Customer Name',
            'customer.email' => 'Customer Email',
            'customer.phone' => 'Customer Phone',
            'customer.address' => 'Customer Address',
            'order.number' => 'Order Number',
            'order.status' => 'Order Status',
            'order.payment_status' => 'Payment Status',
            'order.subtotal' => 'Order Subtotal',
            'order.shipping' => 'Shipping Amount',
            'order.total' => 'Order Total',
            'order.items' => 'Order Items',
            'app.name' => 'App Name',
            'app.url' => 'App URL',
            'company.name' => 'Company Name',
            'company.email' => 'Company Email',
            'company.phone' => 'Company Phone',
            'company.address' => 'Company Address',
            'event' => 'Event',
            'today' => "Today's Date",
        ];
    }

    /** @param  array<string, mixed>  $data */
    public static function replaceTokens(string $text, array $data, bool $escape = true): string
    {
        return (string) preg_replace_callback('/\{\{\s*([^{}]+?)\s*\}\}/', static function (array $matches) use ($data, $escape): string {
            $key = static::normalizeMergeTag($matches[1]);
            $value = data_get($data, $key, data_get($data, str_replace('_', '.', $key)));

            if (! is_scalar($value) && ! $value instanceof Stringable) {
                return $matches[0];
            }

            return $escape ? e((string) $value) : (string) $value;
        }, $text);
    }

    protected static function normalizeMergeTag(string $tag): string
    {
        $normalizedTag = strtolower(trim($tag));

        foreach (static::getMergeTags() as $key => $label) {
            if (in_array($normalizedTag, [strtolower($key), strtolower(str_replace('.', '_', $key)), strtolower($label)], true)) {
                return $key;
            }
        }

        return trim($tag);
    }

    protected static function isTipTapJson(string $content): bool
    {
        $decoded = json_decode($content, true);

        return is_array($decoded) && ($decoded['type'] ?? null) === 'doc';
    }

    /** @param  array<string, mixed>  $data */
    protected static function replaceRenderedMergeTags(string $html, array $data): string
    {
        return (string) preg_replace_callback(
            '/<span[^>]*data-type="mergeTag"[^>]*data-id="([^"]+)"[^>]*><\/span>/i',
            static function (array $matches) use ($data): string {
                $key = static::normalizeMergeTag($matches[1]);
                $value = data_get($data, $key, data_get($data, str_replace('_', '.', $key), ''));

                return e((string) $value);
            },
            $html,
        );
    }

    /** @param  array<string, mixed>  $data */
    public function renderBody(array $data = []): string
    {
        $mergeTags = [];
        foreach (static::getMergeTags() as $key => $label) {
            $value = (string) data_get($data, $key, data_get($data, str_replace('.', '_', $key), '{{ '.$key.' }}'));
            $mergeValue = e($value);

            $mergeTags[$key] = $mergeValue;
            $mergeTags[str_replace('.', '_', $key)] = $mergeValue;
            $mergeTags[$label] = $mergeValue;
        }

        $content = $this->email_body;

        if (! static::isTipTapJson($content)) {
            return static::replaceTokens(static::replaceRenderedMergeTags($content, $data), $data);
        }

        $rendered = RichContentRenderer::make($content)
            ->mergeTags($mergeTags)
            ->toHtml();

        return static::replaceTokens(static::replaceRenderedMergeTags($rendered, $data), $data);
    }
}
