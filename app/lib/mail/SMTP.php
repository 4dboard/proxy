<?php namespace yxorP\app\lib\mail;

use yxorP\app\lib\psr\log\loggerInterface;

class SMTP
{
    const VERSION = '6.6.3';
    const LE = "\r\n";
    const DEFAULT_PORT = 25;
    const MAX_LINE_LENGTH = 998;
    const MAX_REPLY_LENGTH = 512;
    const DEBUG_OFF = 0;
    const DEBUG_CLIENT = 1;
    const DEBUG_SERVER = 2;
    const DEBUG_CONNECTION = 3;
    const DEBUG_LOWLEVEL = 4;
    public int $do_debug = self::DEBUG_OFF;
    public string $Debugoutput = 'echo';
    public bool $do_verp = false;
    public int $Timeout = 300;
    public int $Timelimit = 300;
    protected array $smtp_transaction_id_patterns = ['exim' => '/[\d]{3} OK id=(.*)/', 'sendmail' => '/[\d]{3} 2.0.0 (.*) Message/', 'postfix' => '/[\d]{3} 2.0.0 Ok: queued as (.*)/', 'Microsoft_ESMTP' => '/[0-9]{3} 2.[\d].0 (.*)@(?:.*) Queued mail for delivery/', 'Amazon_SES' => '/[\d]{3} Ok (.*)/', 'SendGrid' => '/[\d]{3} Ok: queued as (.*)/', 'CampaignMonitor' => '/[\d]{3} 2.0.0 OK:([a-zA-Z\d]{48})/', 'Haraka' => '/[\d]{3} Message Queued \((.*)\)/', 'Mailjet' => '/[\d]{3} OK queued as (.*)/',];
    protected $last_smtp_transaction_id;
    protected $smtp_conn;
    protected array $error = ['error' => '', 'detail' => '', 'smtp_code' => '', 'smtp_code_ex' => '',];
    protected $helo_rply;
    protected $server_caps;
    protected string $last_reply = '';

    public function connect($host, $port = null, $timeout = 30, $options = []): bool
    {
        $this->setError('');
        if ($this->connected()) {
            $this->setError('Already connected to a server');
            return false;
        }
        if (empty($port)) {
            $port = self::DEFAULT_PORT;
        }
        $this->edebug("Connection: opening to $host:$port, timeout=$timeout, options=" . (count($options) > 0 ? var_export($options, true) : 'array()'), self::DEBUG_CONNECTION);
        $this->smtp_conn = $this->getSMTPConnection($host, $port, $timeout, $options);
        $this->edebug('Connection: opened', self::DEBUG_CONNECTION);
        $this->last_reply = $this->get_lines();
        $this->edebug('SERVER -> CLIENT: ' . $this->last_reply, self::DEBUG_SERVER);
        $responseCode = (int)substr($this->last_reply, 0, 3);
        if ($responseCode === 220) {
            return true;
        }
        if ($responseCode === 554) {
            $this->quit();
        }
        $this->edebug('Connection: closing due to error', self::DEBUG_CONNECTION);
        $this->close();
        return false;
    }

    public function connected(): bool
    {
        if (is_resource($this->smtp_conn)) {
            $sock_status = stream_get_meta_data($this->smtp_conn);
            if ($sock_status['eof']) {
                $this->edebug('SMTP NOTICE: EOF caught while checking if connected', self::DEBUG_CLIENT);
                $this->close();
                return false;
            }
            return true;
        }
        return false;
    }

    public function close()
    {
        $this->setError('');
        $this->server_caps = null;
        $this->helo_rply = null;
        if (is_resource($this->smtp_conn)) {
            fclose($this->smtp_conn);
            $this->smtp_conn = null;
            $this->edebug('Connection: closed', self::DEBUG_CONNECTION);
        }
    }

    public function getError(): array
    {
        return $this->error;
    }

    protected function setError($message, $detail = '', $smtp_code = '', $smtp_code_ex = '')
    {
        $this->error = ['error' => $message, 'detail' => $detail, 'smtp_code' => $smtp_code, 'smtp_code_ex' => $smtp_code_ex,];
    }

    public function quit($close_on_error = true): bool
    {
        $noerror = $this->sendCommand('QUIT', 'QUIT', 221);
        $err = $this->error;
        if ($noerror || $close_on_error) {
            $this->close();
            $this->error = $err;
        }
        return $noerror;
    }

    public function client_send($data, $command = ''): bool|int
    {
        if (self::DEBUG_LOWLEVEL > $this->do_debug && in_array($command, ['User & Password', 'Username', 'Password'], true)) {
            $this->edebug('CLIENT -> SERVER: [credentials hidden]', self::DEBUG_CLIENT);
        } else {
            $this->edebug('CLIENT -> SERVER: ' . $data, self::DEBUG_CLIENT);
        }
        set_error_handler([$this, 'errorHandler']);
        $result = fwrite($this->smtp_conn, $data);
        restore_error_handler();
        return $result;
    }

    public function startTLS(): bool
    {
        if (!$this->sendCommand('STARTTLS', 'STARTTLS', 220)) {
            return false;
        }
        $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        }
        set_error_handler([$this, 'errorHandler']);
        $crypto_ok = stream_socket_enable_crypto($this->smtp_conn, true, $crypto_method);
        restore_error_handler();
        return (bool)$crypto_ok;
    }

    public function authenticate($username, $password, $authtype = null, $OAuth = null): bool
    {
        if (!$this->server_caps) {
            $this->setError('Authentication is not allowed before HELO/EHLO');
            return false;
        }
        if (array_key_exists('EHLO', $this->server_caps)) {
            if (!array_key_exists('AUTH', $this->server_caps)) {
                $this->setError('Authentication is not allowed at this stage');
                return false;
            }
            $this->edebug('Auth method requested: ' . ($authtype ?: 'UNSPECIFIED'), self::DEBUG_LOWLEVEL);
            $this->edebug('Auth methods available on the server: ' . implode(',', $this->server_caps['AUTH']), self::DEBUG_LOWLEVEL);
            if (null !== $authtype && !in_array($authtype, $this->server_caps['AUTH'], true)) {
                $this->edebug('Requested auth method not available: ' . $authtype, self::DEBUG_LOWLEVEL);
                $authtype = null;
            }
            if (empty($authtype)) {
                foreach (['CRAM-MD5', 'LOGIN', 'PLAIN', 'XOAUTH2'] as $method) {
                    if (in_array($method, $this->server_caps['AUTH'], true)) {
                        $authtype = $method;
                        break;
                    }
                }
                if (empty($authtype)) {
                    $this->setError('No supported authentication methods found');
                    return false;
                }
                $this->edebug('Auth method selected: ' . $authtype, self::DEBUG_LOWLEVEL);
            }
            if (!in_array($authtype, $this->server_caps['AUTH'], true)) {
                $this->setError("The requested authentication method \"$authtype\" is not supported by the server");
                return false;
            }
        } elseif (empty($authtype)) {
            $authtype = 'LOGIN';
        }
        switch ($authtype) {
            case 'PLAIN':
                if (!$this->sendCommand('AUTH', 'AUTH PLAIN', 334)) {
                    return false;
                }
                if (!$this->sendCommand('User & Password', base64_encode("\0" . $username . "\0" . $password), 235)) {
                    return false;
                }
                break;
            case 'LOGIN':
                if (!$this->sendCommand('AUTH', 'AUTH LOGIN', 334)) {
                    return false;
                }
                if (!$this->sendCommand('Username', base64_encode($username), 334)) {
                    return false;
                }
                if (!$this->sendCommand('Password', base64_encode($password), 235)) {
                    return false;
                }
                break;
            case 'CRAM-MD5':
                if (!$this->sendCommand('AUTH CRAM-MD5', 'AUTH CRAM-MD5', 334)) {
                    return false;
                }
                $challenge = base64_decode(substr($this->last_reply, 4));
                $response = $username . ' ' . $this->hmac($challenge, $password);
                return $this->sendCommand('Username', base64_encode($response), 235);
            case 'XOAUTH2':
                if (null === $OAuth) {
                    return false;
                }
                $oauth = $OAuth->getOauth64();
                if (!$this->sendCommand('AUTH', 'AUTH XOAUTH2 ' . $oauth, 235)) {
                    return false;
                }
                break;
            default:
                $this->setError("Authentication method \"$authtype\" is not supported");
                return false;
        }
        return true;
    }

    public function data($msg_data): bool
    {
        if (!$this->sendCommand('DATA', 'DATA', 354)) {
            return false;
        }
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $msg_data));
        $field = substr($lines[0], 0, strpos($lines[0], ':'));
        $in_headers = false;
        if (!empty($field) && !str_contains($field, ' ')) {
            $in_headers = true;
        }
        foreach ($lines as $line) {
            $lines_out = [];
            if ($in_headers && $line === '') {
                $in_headers = false;
            }
            while (isset($line[self::MAX_LINE_LENGTH])) {
                $pos = strrpos(substr($line, 0, self::MAX_LINE_LENGTH), ' ');
                if (!$pos) {
                    $pos = self::MAX_LINE_LENGTH - 1;
                    $lines_out[] = substr($line, 0, $pos);
                    $line = substr($line, $pos);
                } else {
                    $lines_out[] = substr($line, 0, $pos);
                    $line = substr($line, $pos + 1);
                }
                if ($in_headers) {
                    $line = "\t" . $line;
                }
            }
            $lines_out[] = $line;
            foreach ($lines_out as $line_out) {
                if (!empty($line_out) && $line_out[0] === '.') {
                    $line_out = '.' . $line_out;
                }
                $this->client_send($line_out . static::LE, 'DATA');
            }
        }
        $savetimelimit = $this->Timelimit;
        $this->Timelimit *= 2;
        $result = $this->sendCommand('DATA END', '.', 250);
        $this->recordLastTransactionID();
        $this->Timelimit = $savetimelimit;
        return $result;
    }

    public function getLastReply(): string
    {
        return $this->last_reply;
    }

    public function hello($host = ''): bool
    {
        if ($this->sendHello('EHLO', $host)) {
            return true;
        }
        if (str_starts_with($this->helo_rply, '421')) {
            return false;
        }
        return $this->sendHello('HELO', $host);
    }

    public function mail($from): bool
    {
        $useVerp = ($this->do_verp ? ' XVERP' : '');
        return $this->sendCommand('MAIL FROM', 'MAIL FROM:<' . $from . '>' . $useVerp, 250);
    }

    public function recipient($address, $dsn = ''): bool
    {
        if (empty($dsn)) {
            $rcpt = 'RCPT TO:<' . $address . '>';
        } else {
            $dsn = strtoupper($dsn);
            $notify = [];
            if (str_contains($dsn, 'NEVER')) {
                $notify[] = 'NEVER';
            } else {
                foreach (['SUCCESS', 'FAILURE', 'DELAY'] as $value) {
                    if (str_contains($dsn, $value)) {
                        $notify[] = $value;
                    }
                }
            }
            $rcpt = 'RCPT TO:<' . $address . '> NOTIFY=' . implode(',', $notify);
        }
        return $this->sendCommand('RCPT TO', $rcpt, [250, 251]);
    }

    public function reset(): bool
    {
        return $this->sendCommand('RSET', 'RSET', 250);
    }

    public function sendAndMail($from): bool
    {
        return $this->sendCommand('SAML', "SAML FROM:$from", 250);
    }

    public function verify($name): bool
    {
        return $this->sendCommand('VRFY', "VRFY $name", [250, 251]);
    }

    public function noop(): bool
    {
        return $this->sendCommand('NOOP', 'NOOP', 250);
    }

    public function turn(): bool
    {
        $this->setError('The SMTP TURN command is not implemented');
        $this->edebug('SMTP NOTICE: ' . $this->error['error'], self::DEBUG_CLIENT);
        return false;
    }

    public function getServerExtList()
    {
        return $this->server_caps;
    }

    public function getServerExt($name)
    {
        if (!$this->server_caps) {
            $this->setError('No HELO/EHLO was sent');
            return null;
        }
        if (!array_key_exists($name, $this->server_caps)) {
            if ('HELO' === $name) {
                return $this->server_caps['EHLO'];
            }
            if ('EHLO' === $name || array_key_exists('EHLO', $this->server_caps)) {
                return false;
            }
            $this->setError('HELO handshake was used; No information about server extensions available');
            return null;
        }
        return $this->server_caps[$name];
    }

    public function setVerp($enabled = false)
    {
        $this->do_verp = $enabled;
    }

    public function getVerp(): bool
    {
        return $this->do_verp;
    }

    public function getDebugOutput(): string
    {
        return $this->Debugoutput;
    }

    public function setDebugOutput($method = 'echo')
    {
        $this->Debugoutput = $method;
    }

    public function setDebugLevel($level = 0)
    {
        $this->do_debug = $level;
    }

    public function getDebugLevel(): int
    {
        return $this->do_debug;
    }

    public function getTimeout(): int
    {
        return $this->Timeout;
    }

    public function setTimeout($timeout = 0)
    {
        $this->Timeout = $timeout;
    }

    public function getLastTransactionID()
    {
        return $this->last_smtp_transaction_id;
    }

    protected function edebug($str, $level = 0)
    {
        if ($level > $this->do_debug) {
            return;
        }
        if (is_callable($this->Debugoutput) && !in_array($this->Debugoutput, ['error_log', 'html', 'echo'])) {
            call_user_func($this->Debugoutput, $str, $level);
            return;
        }
        switch ($this->Debugoutput) {
            case 'error_log':
                error_log($str);
                break;
            case 'html':
                echo gmdate('Y-m-d H:i:s'), ' ', htmlentities(preg_replace('/[\r\n]+/', '', $str), ENT_QUOTES, 'UTF-8'), "<br>\n";
                break;
            case 'echo':
            default:
                $str = preg_replace('/\r\n|\r/m', "\n", $str);
                echo gmdate('Y-m-d H:i:s'), "\t", trim(str_replace("\n", "\n                   \t                  ", trim($str))), "\n";
        }
    }

    protected function getSMTPConnection($host, $port = null, $timeout = 30, $options = []): bool
    {
        static $streamok;
        if (null === $streamok) {
            $streamok = function_exists('stream_socket_client');
        }
        $errno = 0;
        $errstr = '';
        if ($streamok) {
            $socket_context = stream_context_create($options);
            set_error_handler([$this, 'errorHandler']);
            $connection = stream_socket_client($host . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $socket_context);
        } else {
            $this->edebug('Connection: stream_socket_client not available, falling back to fsockopen', self::DEBUG_CONNECTION);
            set_error_handler([$this, 'errorHandler']);
            $connection = fsockopen($host, $port, $errno, $errstr, $timeout);
        }
        restore_error_handler();
        if (!is_resource($connection)) {
            $this->setError('Failed to connect to server', '', (string)$errno, $errstr);
            $this->edebug('SMTP ERROR: ' . $this->error['error'] . ": $errstr ($errno)", self::DEBUG_CLIENT);
            return false;
        }
        if (!str_starts_with(PHP_OS, 'WIN')) {
            $max = (int)ini_get('max_execution_time');
            if (0 !== $max && $timeout > $max && !str_contains(ini_get('disable_functions'), 'set_time_limit')) {
                @set_time_limit($timeout);
            }
            stream_set_timeout($connection, $timeout, 0);
        }
        return $connection;
    }

    protected function get_lines(): string
    {
        if (!is_resource($this->smtp_conn)) {
            return '';
        }
        $data = '';
        $endtime = 0;
        stream_set_timeout($this->smtp_conn, $this->Timeout);
        if ($this->Timelimit > 0) {
            $endtime = time() + $this->Timelimit;
        }
        $selR = [$this->smtp_conn];
        $selW = null;
        while (is_resource($this->smtp_conn) && !feof($this->smtp_conn)) {
            set_error_handler([$this, 'errorHandler']);
            $n = stream_select($selR, $selW, $selW, $this->Timelimit);
            restore_error_handler();
            if ($n === false) {
                $message = $this->getError()['detail'];
                $this->edebug('SMTP -> get_lines(): select failed (' . $message . ')', self::DEBUG_LOWLEVEL);
                if (stripos($message, 'interrupted system call') !== false) {
                    $this->edebug('SMTP -> get_lines(): retrying stream_select', self::DEBUG_LOWLEVEL);
                    $this->setError('');
                    continue;
                }
                break;
            }
            if (!$n) {
                $this->edebug('SMTP -> get_lines(): select timed-out in (' . $this->Timelimit . ' sec)', self::DEBUG_LOWLEVEL);
                break;
            }
            $str = @fgets($this->smtp_conn, self::MAX_REPLY_LENGTH);
            $this->edebug('SMTP INBOUND: "' . trim($str) . '"', self::DEBUG_LOWLEVEL);
            $data .= $str;
            if (!isset($str[3]) || $str[3] === ' ' || $str[3] === "\r" || $str[3] === "\n") {
                break;
            }
            $info = stream_get_meta_data($this->smtp_conn);
            if ($info['timed_out']) {
                $this->edebug('SMTP -> get_lines(): stream timed-out (' . $this->Timeout . ' sec)', self::DEBUG_LOWLEVEL);
                break;
            }
            if ($endtime && time() > $endtime) {
                $this->edebug('SMTP -> get_lines(): timelimit reached (' . $this->Timelimit . ' sec)', self::DEBUG_LOWLEVEL);
                break;
            }
        }
        return $data;
    }

    protected function sendCommand($command, $commandstring, $expect): bool
    {
        if (!$this->connected()) {
            $this->setError("Called $command without being connected");
            return false;
        }
        if ((str_contains($commandstring, "\n")) || (str_contains($commandstring, "\r"))) {
            $this->setError("Command '$command' contained line breaks");
            return false;
        }
        $this->client_send($commandstring . static::LE, $command);
        $this->last_reply = $this->get_lines();
        $matches = [];
        if (preg_match('/^([\d]{3})[ -](?:([\d]\\.[\d]\\.[\d]{1,2}) )?/', $this->last_reply, $matches)) {
            $code = (int)$matches[1];
            $code_ex = (count($matches) > 2 ? $matches[2] : null);
            $detail = preg_replace("/{$code}[ -]" . ($code_ex ? str_replace('.', '\\.', $code_ex) . ' ' : '') . '/m', '', $this->last_reply);
        } else {
            $code = (int)substr($this->last_reply, 0, 3);
            $code_ex = null;
            $detail = substr($this->last_reply, 4);
        }
        $this->edebug('SERVER -> CLIENT: ' . $this->last_reply, self::DEBUG_SERVER);
        if (!in_array($code, (array)$expect, true)) {
            $this->setError("$command command failed", $detail, $code, $code_ex);
            $this->edebug('SMTP ERROR: ' . $this->error['error'] . ': ' . $this->last_reply, self::DEBUG_CLIENT);
            return false;
        }
        if ($command !== 'RSET') {
            $this->setError('');
        }
        return true;
    }

    protected function hmac($data, $key): bool|string
    {
        if (function_exists('hash_hmac')) {
            return hash_hmac('md5', $data, $key);
        }
        $bytelen = 64;
        if (strlen($key) > $bytelen) {
            $key = pack('H*', md5($key));
        }
        $key = str_pad($key, $bytelen, chr(0x00));
        $ipad = str_pad('', $bytelen, chr(0x36));
        $opad = str_pad('', $bytelen, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;
        return md5($k_opad . pack('H*', md5($k_ipad . $data)));
    }

    protected function recordLastTransactionID(): bool|string|null
    {
        $reply = $this->getLastReply();
        if (empty($reply)) {
            $this->last_smtp_transaction_id = null;
        } else {
            $this->last_smtp_transaction_id = false;
            foreach ($this->smtp_transaction_id_patterns as $smtp_transaction_id_pattern) {
                $matches = [];
                if (preg_match($smtp_transaction_id_pattern, $reply, $matches)) {
                    $this->last_smtp_transaction_id = trim($matches[1]);
                    break;
                }
            }
        }
        return $this->last_smtp_transaction_id;
    }

    protected function sendHello($hello, $host): bool
    {
        $noerror = $this->sendCommand($hello, $hello . ' ' . $host, 250);
        $this->helo_rply = $this->last_reply;
        if ($noerror) {
            $this->parseHelloFields($hello);
        } else {
            $this->server_caps = null;
        }
        return $noerror;
    }

    protected function parseHelloFields($type)
    {
        $this->server_caps = [];
        $lines = explode("\n", $this->helo_rply);
        foreach ($lines as $n => $s) {
            $s = trim(substr($s, 4));
            if (empty($s)) {
                continue;
            }
            $fields = explode(' ', $s);
            if (!empty($fields)) {
                if (!$n) {
                    $name = $type;
                    $fields = $fields[0];
                } else {
                    $name = array_shift($fields);
                    switch ($name) {
                        case 'SIZE':
                            $fields = ($fields ? $fields[0] : 0);
                            break;
                        case 'AUTH':
                            if (!is_array($fields)) {
                                $fields = [];
                            }
                            break;
                        default:
                            $fields = true;
                    }
                }
                $this->server_caps[$name] = $fields;
            }
        }
    }

    protected function errorHandler($errno, $errmsg, $errfile = '', $errline = 0)
    {
        $notice = 'Connection failed.';
        $this->setError($notice, $errmsg, (string)$errno);
        $this->edebug("$notice Error #$errno: $errmsg [$errfile line $errline]", self::DEBUG_CONNECTION);
    }
}
