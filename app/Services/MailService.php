<?php

declare(strict_types=1);

/**
 * Chooses how a message leaves the application.
 *
 * The driver is configuration rather than code because the right answer
 * depends entirely on the host: `api` works everywhere including free shared
 * hosting, `smtp` needs outbound ports a free host usually blocks, and `mail`
 * needs a local MTA that a free host usually does not run.
 */
final class MailService
{
    public const DRIVERS = ['log', 'api', 'smtp', 'mail'];

    public function send(string $to, string $subject, string $html, string $text): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // A subject is one header line, so a newline in it could inject others.
        // Runs of whitespace collapse too, or stripping "\r\n" leaves a gap.
        $subject = trim((string) preg_replace('/\s+/u', ' ', $subject));

        $driver = $this->driver();
        if ($driver === 'log') {
            return $this->log($to, $subject, $text);
        }

        $fromAddress = (string) env('MAIL_FROM_ADDRESS', 'noreply@example.com');
        $fromName = str_replace(["\r", "\n"], '', (string) env('MAIL_FROM_NAME', APP_NAME));

        $sent = match ($driver) {
            'api' => $this->sendViaApi($to, $subject, $html, $text, $fromAddress, $fromName),
            'smtp' => $this->sendViaSmtp($to, $subject, $html, $text, $fromAddress, $fromName),
            default => $this->sendViaMail($to, $subject, $html, $text, $fromAddress, $fromName),
        };

        if (!$sent) {
            // A failed reset email is invisible to the person waiting for it,
            // so the reason belongs somewhere the operator can find it.
            $this->log($to, $subject, 'NOT SENT via ' . $driver . '. ' . $this->lastError);
        }

        return $sent;
    }

    private string $lastError = '';

    /** Sending is off by default outside production so tests never post mail. */
    private function driver(): string
    {
        $driver = (string) env('MAIL_DRIVER', APP_ENV === 'production' ? 'api' : 'log');

        return in_array($driver, self::DRIVERS, true) ? $driver : 'log';
    }

    private function sendViaApi(
        string $to,
        string $subject,
        string $html,
        string $text,
        string $fromAddress,
        string $fromName
    ): bool {
        $mailer = new HttpMailer(
            (string) env('MAIL_API_PROVIDER', 'brevo'),
            (string) env('MAIL_API_KEY', '')
        );

        $sent = $mailer->send($to, $subject, $html, $text, $fromAddress, $fromName);
        $this->lastError = $mailer->error();

        return $sent;
    }

    private function sendViaSmtp(
        string $to,
        string $subject,
        string $html,
        string $text,
        string $fromAddress,
        string $fromName
    ): bool {
        $mailer = new SmtpMailer(
            (string) env('MAIL_HOST', 'localhost'),
            (int) env('MAIL_PORT', 587),
            (string) env('MAIL_USERNAME', ''),
            (string) env('MAIL_PASSWORD', ''),
            (string) env('MAIL_ENCRYPTION', 'tls')
        );

        $headers = $this->headers($to, $subject, $fromAddress, $fromName);
        $sent = $mailer->send($to, $subject, $headers . self::boundaryBody($html, $text), $fromAddress);
        $this->lastError = $mailer->error();

        return $sent;
    }

    private function sendViaMail(
        string $to,
        string $subject,
        string $html,
        string $text,
        string $fromAddress,
        string $fromName
    ): bool {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . self::BOUNDARY . '"',
            'From: ' . $fromName . ' <' . $fromAddress . '>',
        ];

        $sent = @mail($to, $subject, self::boundaryBody($html, $text), implode("\r\n", $headers));
        $this->lastError = $sent ? '' : 'The host rejected mail(), which free hosts normally disable.';

        return $sent;
    }

    private const BOUNDARY = 'brightcv-alt-boundary';

    /** The header block an SMTP DATA command has to carry itself. */
    private function headers(string $to, string $subject, string $fromAddress, string $fromName): string
    {
        $lines = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $fromName . ' <' . $fromAddress . '>',
            'To: <' . $to . '>',
            'Subject: ' . $subject,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . self::BOUNDARY . '"',
        ];

        return implode("\r\n", $lines) . "\r\n\r\n";
    }

    /**
     * Both versions of the message, in one body.
     *
     * The plain text part used to be accepted and then thrown away, which cost
     * deliverability: a message with no text alternative scores worse with
     * spam filters, and reads as empty in a client set to plain text.
     */
    private static function boundaryBody(string $html, string $text): string
    {
        $boundary = self::BOUNDARY;

        return "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . $text . "\r\n\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . $html . "\r\n\r\n"
            . "--{$boundary}--\r\n";
    }

    private function log(string $to, string $subject, string $text): bool
    {
        $directory = STORAGE_PATH . '/logs';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $message = sprintf(
            "[%s]\nTo: %s\nSubject: %s\n%s\n\n",
            date(DATE_ATOM),
            $to,
            $subject,
            $text
        );

        return file_put_contents($directory . '/mail.log', $message, FILE_APPEND | LOCK_EX) !== false;
    }
}
