<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Support\Settings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Every transactional e-mail in the product.
 *
 * The subject and body come from the email_templates table so the client can
 * edit them without a deploy; if a template is missing or switched off, the
 * seeded Blade fallback for that key renders instead. The 600px table shell,
 * the navy header band and the legal footer are the layout's job — the Baukasten
 * section of the design is explicit that changes belong to the building block,
 * never to a single template.
 */
class TemplateMail extends Mailable implements ShouldQueue
{
    /** False until looked up; null once looked up and absent. */
    private EmailTemplate|null|false $resolvedTemplate = false;

    use Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public string $templateKey,
        public array $data = [],
        public array $attachmentPaths = [],
    ) {}

    public function envelope(): Envelope
    {
        $template = $this->template();

        $subject = $template !== null
            ? $template->renderSubject($this->data)
            : ($this->data['subject'] ?? 'Nachricht der DKGZ');

        return new Envelope(
            from: new Address(
                Settings::get('email.from_address', config('mail.from.address')),
                Settings::get('email.from_name', config('mail.from.name')),
            ),
            replyTo: filled(Settings::get('email.reply_to'))
                ? [new Address(Settings::get('email.reply_to'))]
                : [],
            subject: $subject,
        );
    }

    /**
     * Templates that are not tied to a single transaction. Only these carry a
     * List-Unsubscribe header — offering to unsubscribe from "your assignment
     * was accepted" would be both meaningless and, for a partner relying on it,
     * actively harmful.
     */
    private const BULK_TEMPLATES = ['provisionsabrechnung'];

    /** Set by Mailer so the delivery listener can mark the right row sent. */
    public ?int $logId = null;

    public function headers(): Headers
    {
        $unsubscribe = Settings::get('email.unsubscribe_address') ?: Settings::get('email.reply_to');
        $bounce = Settings::get('email.bounce_address');

        $text = [];

        if ($this->logId !== null) {
            $text['X-DKGZ-Log'] = (string) $this->logId;
        }

        if (filled($bounce)) {
            $text['Return-Path'] = "<{$bounce}>";
        }

        if (in_array($this->templateKey, self::BULK_TEMPLATES, true) && filled($unsubscribe)) {
            $text['List-Unsubscribe'] = "<mailto:{$unsubscribe}?subject=Abmeldung%20Provisionsabrechnung>";
            $text['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
        }

        return new Headers(text: $text);
    }

    public function content(): Content
    {
        $template = $this->template();

        return new Content(
            view: 'emails.layout',
            text: 'emails.plain',
            with: [
                'templateKey' => $this->templateKey,
                'bodyHtml' => $template?->renderBody($this->data),
                'preheader' => $template?->preheader_de ?? ($this->data['preheader'] ?? ''),
                'eyebrow' => $this->data['eyebrow'] ?? $template?->name_de,
                'headline' => $this->data['headline'] ?? null,
                'salutation' => $this->data['salutation'] ?? null,
                'rows' => $this->data['rows'] ?? [],
                'dataTitle' => $this->data['dataTitle'] ?? null,
                'reference' => $this->data['referenz'] ?? null,
                'referenceLabel' => $this->data['refLabel'] ?? 'Ihre Vorgangsnummer',
                'maskNotice' => (bool) ($this->data['mask'] ?? false),
                'quote' => $this->data['quote'] ?? null,
                'quoteBy' => $this->data['quoteBy'] ?? null,
                'showScale' => (bool) ($this->data['scale'] ?? false),
                'cta' => $this->data['cta'] ?? null,
                'ctaUrl' => $this->data['cta_url'] ?? null,
                'portrait' => $this->data['sv_bild'] ?? null,
                'portraitInitials' => $this->data['sv_initialen'] ?? null,
                'portraitCaption' => $this->data['sv_bildunterschrift'] ?? null,
                'note' => $template?->renderNote($this->data) ?? ($this->data['note'] ?? null),
                'footnote' => $this->data['footnote'] ?? Settings::get('email.footer_text'),
                // The sign-off is one setting for every message: it was fixed
                // text in the layout, so the one line every mail ends with was
                // the one line nobody could change.
                'signoff' => Settings::get('email.signoff', "Mit freundlichen Grüßen\nDeutsche KFZ-Gutachterzentrale"),
                'vars' => $this->data,
            ],
        );
    }

    public function attachments(): array
    {
        return collect($this->attachmentPaths)
            ->map(fn (string $path) => Attachment::fromStorageDisk('private', $path))
            ->all();
    }

    /**
     * The template behind this message, looked up once per message.
     *
     * This used to memoise into a `static` array, which lives as long as the
     * PHP process rather than as long as the mail: a queue worker that had once
     * rendered a template kept sending that same copy after an admin edited it,
     * and a worker that met the key before it existed cached the absence and
     * sent every later message with an empty body. Both are invisible from the
     * admin panel, where the preview reads from the database and looks right.
     */
    private function template(): ?EmailTemplate
    {
        if ($this->resolvedTemplate === false) {
            $this->resolvedTemplate = EmailTemplate::where('key', $this->templateKey)
                ->where('is_active', true)
                ->first();
        }

        return $this->resolvedTemplate;
    }
}
