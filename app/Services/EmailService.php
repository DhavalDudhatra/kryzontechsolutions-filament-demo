<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\EmailTemplateMailable;
use App\Models\EmailTemplate;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail as MailFacade;

final class EmailService
{
    /**
     * Send a database-driven email template.
     *
     * @param  array<string, mixed>  $data
     */
    public function sendTemplate(string $templateKey, string $email, array $data = []): bool
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
            MailFacade::to($email)->send(new EmailTemplateMailable($template, $data));

            Log::info('Email sent successfully', [
                'template_key' => $templateKey,
                'recipient' => $email,
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::error('Failed to send email', [
                'template_key' => $templateKey,
                'recipient' => $email,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send a database-driven email template via queue.
     *
     * @param  array<string, mixed>  $data
     */
    public function queueTemplate(string $templateKey, string $email, array $data = [], ?string $queue = null): bool
    {
        $template = EmailTemplate::query()
            ->where('template_key', $templateKey)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            Log::warning('Email template not found or inactive (queued)', [
                'template_key' => $templateKey,
                'recipient' => $email,
            ]);

            return false;
        }

        try {
            $mailable = new EmailTemplateMailable($template, $data);

            if ($queue !== null) {
                $mailable->onQueue($queue);
            }

            MailFacade::to($email)->queue($mailable);

            Log::info('Email queued successfully', [
                'template_key' => $templateKey,
                'recipient' => $email,
                'queue' => $queue,
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::error('Failed to queue email', [
                'template_key' => $templateKey,
                'recipient' => $email,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send a custom Mailable instance directly.
     *
     * @param  array<string, mixed>  $data
     * @param  string|array<string>  $to
     */
    public function send(Mailable $mailable, string|array $to, array $data = []): bool
    {
        try {
            if (is_array($to)) {
                MailFacade::to(...$to)->send($mailable);
            } else {
                MailFacade::to($to)->send($mailable);
            }

            return true;
        } catch (\Throwable $exception) {
            Log::error('Failed to send custom mailable', [
                'mailable' => $mailable::class,
                'recipient' => is_array($to) ? implode(', ', $to) : $to,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Queue a custom Mailable instance.
     *
     * @param  array<string, mixed>  $data
     * @param  string|array<string>  $to
     */
    public function queue(Mailable $mailable, string|array $to, ?string $queue = null, array $data = []): bool
    {
        try {
            if ($queue !== null && method_exists($mailable, 'onQueue')) {
                $mailable->onQueue($queue);
            }

            if (is_array($to)) {
                MailFacade::to(...$to)->queue($mailable);
            } else {
                MailFacade::to($to)->queue($mailable);
            }

            return true;
        } catch (\Throwable $exception) {
            Log::error('Failed to queue custom mailable', [
                'mailable' => $mailable::class,
                'recipient' => is_array($to) ? implode(', ', $to) : $to,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
