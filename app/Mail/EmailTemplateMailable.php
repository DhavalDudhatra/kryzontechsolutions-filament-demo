<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Setting;
use App\Models\User;
use App\Support\DateFormats;
use App\Support\SettingsStore;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail as MailFacade;
use Throwable;

class EmailTemplateMailable extends Mailable
{
    use Queueable;

    /** @var array<string, string|null> */
    public array $brand;

    public string $mailSubject;

    public string $mailBody;

    /** @var list<string> */
    public array $ccRecipients;

    /** @var list<string> */
    public array $bccRecipients;

    /** @param array<string, mixed> $data */
    public function __construct(EmailTemplate $template, array $data = [])
    {
        $this->brand = self::brand();
        $this->ccRecipients = self::recipients($template->cc);
        $this->bccRecipients = self::recipients($template->bcc);

        $data['app'] = [
            'name' => $this->brand['name'] ?? config('app.name'),
            'url' => config('app.url'),
        ];
        $data['company'] = [
            'name' => $this->brand['name'],
            'email' => $this->brand['email'],
            'phone' => $this->brand['phone'],
            'address' => $this->brand['address'],
        ];
        $data['today'] = now()->format(DateFormats::date());

        $this->mailSubject = EmailTemplate::replaceTokens($template->email_subject, $data, escape: false);

        $this->mailBody = $template->renderBody($data);
    }

    /**
     * Sends the template to a recipient when an active template exists.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public static function sendTo(string $templateKey, string $email, array $data = [], ?User $user = null): bool
    {
        $template = EmailTemplate::query()
            ->where('template_key', $templateKey)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            Log::warning('Email template not found or inactive', [
                'template_key' => $templateKey,
                'recipient' => $email,
            ]);

            return false;
        }

        try {
            MailFacade::to($email)->send(new self($template, $data));
        } catch (Throwable $exception) {
            Log::error('Failed to send email', [
                'template_key' => $templateKey,
                'recipient' => $email,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        Log::info('Email sent successfully', [
            'template_key' => $templateKey,
            'recipient' => $email,
        ]);

        return true;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
            from: new Address(
                $this->brand['email'] ?? (string) config('mail.from.address'),
                $this->brand['name'] ?? (string) config('mail.from.name'),
            ),
            cc: array_map(static fn (string $email): Address => new Address($email), $this->ccRecipients),
            bcc: array_map(static fn (string $email): Address => new Address($email), $this->bccRecipients),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.template',
            with: [
                'body' => $this->mailBody,
                'brand' => $this->brand,
            ],
        );
    }

    /**
     * Keeps only valid addresses so one bad stored value cannot break sending.
     *
     * @param  array<int, mixed>|string|null  $emails
     * @return list<string>
     */
    public static function recipients(array|string|null $emails): array
    {
        if (is_string($emails)) {
            $emails = [$emails];
        }

        // Legacy rows hold comma-joined emails in a single entry.
        $joined = implode(',', array_map(strval(...), $emails ?? []));

        return array_values(array_filter(
            array_map(trim(...), explode(',', $joined)),
            static fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
        ));
    }

    /**
     * Branding used by every email, taken from the app settings.
     *
     * @return array<string, string|null>
     */
    protected static function brand(): array
    {
        $settings = SettingsStore::all();

        $color = trim((string) ($settings['primary_color'] ?? ''));
        $contactEmail = $settings['contact_email'] ?? null;

        return [
            'name' => filled($settings['app_name'] ?? null) ? $settings['app_name'] : config('app.name'),
            'logo' => (new Setting)->lightLogoUrl(),
            'color' => preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1 ? $color : '#4f46e5',
            'email' => filter_var($contactEmail, FILTER_VALIDATE_EMAIL) !== false ? $contactEmail : null,
            'phone' => $settings['contact_phone'] ?? null,
            'address' => $settings['company_address'] ?? null,
        ];
    }
}
