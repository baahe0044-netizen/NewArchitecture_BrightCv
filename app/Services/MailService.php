<?php

declare(strict_types=1);

final class MailService
{
    public function send(string $to, string $subject, string $html, string $text): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $driver = (string) env('MAIL_DRIVER', APP_ENV === 'production' ? 'mail' : 'log');
        if ($driver === 'log') {
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

        $fromAddress = (string) env('MAIL_FROM_ADDRESS', 'noreply@example.com');
        $fromName = str_replace(["\r", "\n"], '', (string) env('MAIL_FROM_NAME', APP_NAME));
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromAddress . '>',
        ];

        return mail($to, $subject, $html, implode("\r\n", $headers));
    }
}
