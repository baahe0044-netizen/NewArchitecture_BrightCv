<?php

declare(strict_types=1);

/**
 * Sends mail through a provider's HTTPS API.
 *
 * Free and budget hosts routinely disable PHP's mail() and block outbound
 * connections on the SMTP ports, which leaves password resets silently
 * failing. Port 443 is the one port that is always open, because the site
 * itself is served over it, so an HTTPS API is the transport that works
 * everywhere. It is the right default for shared hosting.
 *
 * Two providers are supported because their free tiers are the ones worth
 * pointing someone at; both speak plain JSON, so the difference is only the
 * shape of the request.
 */
final class HttpMailer
{
    /** Providers this class knows how to talk to. */
    public const PROVIDERS = ['brevo', 'resend'];

    private const TIMEOUT_SECONDS = 15;

    private string $provider;
    private string $key;
    private string $error = '';

    public function __construct(string $provider, string $key)
    {
        $this->provider = in_array($provider, self::PROVIDERS, true) ? $provider : 'brevo';
        $this->key = $key;
    }

    /** Why the last send failed, for the log. Never contains the API key. */
    public function error(): string
    {
        return $this->error;
    }

    public function send(
        string $to,
        string $subject,
        string $html,
        string $text,
        string $fromAddress,
        string $fromName
    ): bool {
        if ($this->key === '') {
            $this->error = 'No API key configured.';
            return false;
        }

        [$url, $headers, $payload] = $this->provider === 'resend'
            ? $this->resendRequest($to, $subject, $html, $text, $fromAddress, $fromName)
            : $this->brevoRequest($to, $subject, $html, $text, $fromAddress, $fromName);

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            $this->error = 'Could not encode the message.';
            return false;
        }

        return $this->post($url, $headers, $body);
    }

    /** @return array{0: string, 1: list<string>, 2: array<string, mixed>} */
    private function brevoRequest(
        string $to,
        string $subject,
        string $html,
        string $text,
        string $fromAddress,
        string $fromName
    ): array {
        return [
            'https://api.brevo.com/v3/smtp/email',
            ['api-key: ' . $this->key, 'content-type: application/json', 'accept: application/json'],
            [
                'sender' => ['name' => $fromName, 'email' => $fromAddress],
                'to' => [['email' => $to]],
                'subject' => $subject,
                'htmlContent' => $html,
                'textContent' => $text,
            ],
        ];
    }

    /** @return array{0: string, 1: list<string>, 2: array<string, mixed>} */
    private function resendRequest(
        string $to,
        string $subject,
        string $html,
        string $text,
        string $fromAddress,
        string $fromName
    ): array {
        return [
            'https://api.resend.com/emails',
            ['Authorization: Bearer ' . $this->key, 'Content-Type: application/json'],
            [
                'from' => $fromName . ' <' . $fromAddress . '>',
                'to' => [$to],
                'subject' => $subject,
                'html' => $html,
                'text' => $text,
            ],
        ];
    }

    /**
     * POST the message, over curl where it exists and stream wrappers where it
     * does not. Shared hosts disable one or the other often enough that
     * relying on a single one costs deliverability for no good reason.
     *
     * @param list<string> $headers
     */
    private function post(string $url, array $headers, string $body): bool
    {
        if (function_exists('curl_init')) {
            return $this->postWithCurl($url, $headers, $body);
        }
        if (ini_get('allow_url_fopen')) {
            return $this->postWithStream($url, $headers, $body);
        }

        $this->error = 'Neither curl nor allow_url_fopen is available to reach the mail provider.';
        return false;
    }

    /** @param list<string> $headers */
    private function postWithCurl(string $url, array $headers, string $body): bool
    {
        $handle = curl_init($url);
        if ($handle === false) {
            $this->error = 'Could not start the request.';
            return false;
        }

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $transportError = curl_error($handle);
        curl_close($handle);

        if ($response === false) {
            $this->error = 'Request failed: ' . $transportError;
            return false;
        }

        return $this->accept($status, is_string($response) ? $response : '');
    }

    /** @param list<string> $headers */
    private function postWithStream(string $url, array $headers, string $body): bool
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => self::TIMEOUT_SECONDS,
                // Read the body of a 4xx rather than turning it into a warning,
                // so the log can say what the provider objected to.
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            $this->error = 'Could not reach the mail provider.';
            return false;
        }

        // $http_response_header is set by the stream wrapper in this scope.
        $status = 0;
        foreach ($http_response_header ?? [] as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $match)) {
                $status = (int) $match[1];
            }
        }

        return $this->accept($status, $response);
    }

    /** Any 2xx means the provider took the message. */
    private function accept(int $status, string $response): bool
    {
        if ($status >= 200 && $status < 300) {
            return true;
        }

        $this->error = 'Provider returned ' . $status . ': ' . mb_substr(trim($response), 0, 300);
        return false;
    }
}
