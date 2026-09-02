<?php

declare(strict_types=1);

/**
 * A small SMTP client, written rather than pulled in.
 *
 * BrightCV has no Composer dependencies and shared hosts have no shell to run
 * `composer install` on, so a library would have to be uploaded by hand and
 * kept up to date by hand. Sending one transactional message needs a short
 * conversation with the server, which is cheaper to own than to vendor.
 *
 * Note that this transport is for hosts that allow outbound connections on the
 * SMTP ports. Free hosts commonly do not; see HttpMailer for those.
 */
final class SmtpMailer
{
    private const TIMEOUT_SECONDS = 15;
    private const CRLF = "\r\n";

    /** @var resource|null */
    private $socket = null;
    private string $error = '';

    public function __construct(
        private string $host,
        private int $port,
        private string $username,
        private string $password,
        /** '', 'tls' (STARTTLS) or 'ssl' (implicit TLS). */
        private string $encryption = 'tls'
    ) {
    }

    /** Why the last send failed, for the log. Never contains the password. */
    public function error(): string
    {
        return $this->error;
    }

    public function send(
        string $to,
        string $subject,
        string $message,
        string $fromAddress
    ): bool {
        if (!$this->connect()) {
            return false;
        }

        try {
            $host = $this->clientName();

            if (!$this->command('EHLO ' . $host, [250])) {
                return false;
            }

            if ($this->encryption === 'tls') {
                if (!$this->command('STARTTLS', [220])) {
                    return false;
                }
                if (!$this->enableCrypto()) {
                    return false;
                }
                // The server forgets what it was told before the handshake.
                if (!$this->command('EHLO ' . $host, [250])) {
                    return false;
                }
            }

            if ($this->username !== '' && !$this->authenticate()) {
                return false;
            }

            if (!$this->command('MAIL FROM:<' . $fromAddress . '>', [250])) {
                return false;
            }
            if (!$this->command('RCPT TO:<' . $to . '>', [250, 251])) {
                return false;
            }
            if (!$this->command('DATA', [354])) {
                return false;
            }

            // A line of a single dot would end the message early, so any such
            // line is escaped with a second dot as the protocol requires.
            $body = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r", "\n"], self::CRLF, $message));

            return $this->command($body . self::CRLF . '.', [250]);
        } finally {
            $this->disconnect();
        }
    }

    private function connect(): bool
    {
        $prefix = $this->encryption === 'ssl' ? 'ssl://' : '';
        $socket = @stream_socket_client(
            $prefix . $this->host . ':' . $this->port,
            $code,
            $reason,
            self::TIMEOUT_SECONDS,
            STREAM_CLIENT_CONNECT
        );

        if ($socket === false) {
            // The usual cause on shared hosting is a blocked outbound port,
            // which looks like a timeout rather than a refusal.
            $this->error = 'Could not connect to ' . $this->host . ':' . $this->port
                . ' (' . $code . ' ' . $reason . '). Free hosts often block these ports.';
            return false;
        }

        $this->socket = $socket;
        stream_set_timeout($socket, self::TIMEOUT_SECONDS);

        return $this->expect([220]);
    }

    private function enableCrypto(): bool
    {
        if (!is_resource($this->socket)) {
            return false;
        }

        $enabled = @stream_socket_enable_crypto(
            $this->socket,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );

        if ($enabled !== true) {
            $this->error = 'The server would not complete the TLS handshake.';
            return false;
        }

        return true;
    }

    private function authenticate(): bool
    {
        if (!$this->command('AUTH LOGIN', [334])) {
            return false;
        }
        if (!$this->command(base64_encode($this->username), [334])) {
            return false;
        }

        // A rejection here is a credential problem; say so without echoing it.
        if (!$this->command(base64_encode($this->password), [235])) {
            $this->error = 'The mail server rejected the username or password.';
            return false;
        }

        return true;
    }

    /**
     * Send one command and check the reply code.
     *
     * @param list<int> $expected
     */
    private function command(string $line, array $expected): bool
    {
        if (!is_resource($this->socket)) {
            return false;
        }
        if (@fwrite($this->socket, $line . self::CRLF) === false) {
            $this->error = 'The connection closed while sending.';
            return false;
        }

        return $this->expect($expected);
    }

    /** @param list<int> $expected */
    private function expect(array $expected): bool
    {
        $reply = $this->readReply();
        if ($reply === null) {
            return false;
        }

        $code = (int) substr($reply, 0, 3);
        if (in_array($code, $expected, true)) {
            return true;
        }

        $this->error = 'Unexpected reply: ' . trim($reply);
        return false;
    }

    /** Read a reply, following the continuation lines of a multi-line one. */
    private function readReply(): ?string
    {
        if (!is_resource($this->socket)) {
            return null;
        }

        $reply = '';
        while (($line = @fgets($this->socket, 1024)) !== false) {
            $reply .= $line;
            // "250-" continues, "250 " ends.
            if (strlen($line) < 4 || $line[3] !== '-') {
                return $reply;
            }
        }

        $this->error = $reply === ''
            ? 'The mail server closed the connection without replying.'
            : 'The reply from the mail server was cut short.';

        return null;
    }

    /** The name given in EHLO; some servers reject one that is not a domain. */
    private function clientName(): string
    {
        $host = parse_url((string) env('APP_URL', ''), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : 'localhost';
    }

    private function disconnect(): void
    {
        if (is_resource($this->socket)) {
            @fwrite($this->socket, 'QUIT' . self::CRLF);
            @fclose($this->socket);
        }
        $this->socket = null;
    }
}
