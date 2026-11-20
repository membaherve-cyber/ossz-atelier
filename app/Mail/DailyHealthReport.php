<?php

namespace App\Mail;

use App\Models\DailyReportRecipient;
use App\Models\DailyReportSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The automated daily business-health e-mail.
 *
 * Sent per-recipient (so a failing address never blocks the others) by
 * the `atelier:daily-report` command / DailyReportController. The body is
 * a self-contained inline-styled Blade view (resources/views/emails/
 * daily-report.blade.php) safe for shared-hosting mail transports.
 */
class DailyHealthReport extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $report  DailyReportService::build() payload
     */
    public function __construct(
        public readonly array $report,
        private readonly DailyReportSetting $settings,
        public readonly ?DailyReportRecipient $recipient = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $date = $this->report['reportDate'] instanceof \DateTimeInterface
            ? $this->report['reportDate']->format('d M Y')
            : now()->format('d M Y');

        return new Envelope(
            subject: 'OSSZ ATELIER — Daily Health Report · '.$date,
            replyTo: $this->settings->reply_to
                ? [new Address($this->settings->reply_to)]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-report',
            with: [
                'report' => $this->report,
                'recipient' => $this->recipient,
                'settings' => $this->settings,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
