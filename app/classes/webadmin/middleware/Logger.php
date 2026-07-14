<?php

declare(strict_types=1);

namespace webadmin\middleware;

use vakata\database\DBInterface;
use Monolog\Logger as Log;
use vakata\http\Request;
use vakata\http\Response;
use Laminas\Diactoros\Response\Serializer as ResponseSerializer;
use Laminas\Diactoros\Request\Serializer as RequestSerializer;
use vakata\random\Generator;

class Logger
{
    protected Log $log;
    protected DBInterface $dbc;
    /** @var array<string> $alwaysLog */
    protected array $alwaysLog;
    /** @var array<string> $privateRequests */
    protected array $privateRequests;
    protected ?string $storage;
    /** @var array<mixed> $context */
    protected array $context;
    protected ?string $certificates;
    protected bool $debug;
    protected string $lastNotice = '';

    /**
     * @param Log $log
     * @param DBInterface $dbc
     * @param array<string> $alwaysLog
     * @param array<string> $privateRequests
     * @param string|null $storage
     * @param array<mixed> $context
     * @param string|null $certificates
     * @param boolean $debug
     */
    public function __construct(
        Log $log,
        DBInterface $dbc,
        array $alwaysLog = [],
        array $privateRequests = [],
        ?string $storage = null,
        array $context = [],
        ?string $certificates = null,
        bool $debug = false
    ) {
        $this->log = $log;
        $this->dbc = $dbc;
        $this->alwaysLog = $alwaysLog;
        foreach ($this->alwaysLog as $k => $v) {
            $this->alwaysLog[$k] = trim($v, '/');
        }
        $this->privateRequests = $privateRequests;
        foreach ($this->privateRequests as $k => $v) {
            $this->privateRequests[$k] = trim($v, '/');
        }
        $this->storage = $storage;
        $this->context = $context;
        $this->certificates = $certificates;
        $this->debug = $debug;

        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
            // do not touch errors where @ is used or that are not marked for reporting
            if ($errno === 0 || !($errno & error_reporting())) {
                return true;
            }
            // do not throw, only log "lightweight" errors
            if (in_array($errno, [ E_NOTICE, E_DEPRECATED, E_USER_NOTICE, E_USER_DEPRECATED ])) {
                $this->lastNotice = 'PHP Notice: ' . $errstr .
                    ($errfile && $errline ? ' in ' . $errfile . ' on line ' . $errline : '');
                $this->log->notice($this->lastNotice);
                return true;
            }
            // throw exception for all others
            throw new \ErrorException($errstr, $errno, $errno, $errfile, $errline);
        });
    }

    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $this->lastNotice = '';
        $lastException = null;
        $time = microtime(true);
        try {
            $uuid = Generator::uuid();
            $res = $next($req)
                ->withHeader('X-Request-UUID', $uuid);
            $done = microtime(true) - $time;
            $res = $res->withHeader('X-Request-Time', sprintf('%01.5f', $done));
            if (!$res->hasHeader('X-Log') && $done >= 5) {
                $res = $res->withHeader('X-Log', 'Processing slow');
            }
            if ($res->hasHeader('X-Log')) {
                $message = $res->getHeaderLine('X-Log');
                if (strpos($message, 'Exception: ') === 0) {
                    $this->log->error(explode('Exception: ', $message, 2)[1] ?? '');
                }
            }
        } catch (\Throwable $e) {
            $lastException = $e;
            $message = $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
            $severity = $e instanceof \ErrorException ? $e->getSeverity() : E_ERROR;
            $context = array_merge(
                $this->context,
                [
                    'request' => $this->serializeRequest($req),
                    'clientip' => (string)$req->getAttribute('client-ip'),
                    'stacktrace' => $e->getTraceAsString()
                ]
            );
            $userObj = $req->getAttribute('user');
            if ($userObj) {
                $user = $context['user'] = $userObj->getID();
                $username = $context['username'] = $userObj->name . ($userObj->impersonated ? ' *' : '');
            }
            switch ($severity) {
                case E_ERROR:
                case E_RECOVERABLE_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                case E_PARSE:
                    $this->log->error($message, $context);
                    break;
                case E_WARNING:
                case E_USER_WARNING:
                case E_CORE_WARNING:
                case E_COMPILE_WARNING:
                    $this->log->warning($message, $context);
                    break;
                case E_NOTICE:
                case E_USER_NOTICE:
                    $this->log->notice($message, $context);
                    break;
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                    $this->log->info($message, $context);
                    break;
                default:
                    $this->log->critical($message, $context);
                    break;
            }
            $code = $e->getCode() >= 200 && $e->getCode() <= 503 ? $e->getCode() : 500;
            $html = '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Error ' . htmlspecialchars((string)$code) . '</title>
                <style>
                    body { font-family: "Helvetica Neue", Arial, sans-serif; text-align:center;
                        font-size:1rem; background-color: #900000; color:white; }
                    h1 { margin:0 0 2rem 0; padding:0; font-size:8rem; }
                    h2 { margin:0 0 2rem 0; padding:0; font-size:2.6rem; }
                    h3 { display:none; margin:4rem 0 2rem 0; padding:0; font-size:1.4rem; }
                    a { background:#e98724; color:white; display:inline-block; padding:1rem 2rem;
                        text-decoration:none; border-radius:5px; }
                    p { position: fixed; bottom:0; left:0; right:0; }
                    pre { text-align:left; margin:2rem; }
                </style>
            </head>
            <body>
                ' .
                ($this->debug ?
                    '<h1>' . htmlspecialchars((string)$code) . '</h1>
                     <pre style="text-align:center;">' . htmlspecialchars($e->getMessage()) . '<br />
                     ' . htmlspecialchars($e->getFile() . ' : ' . $e->getLine()) . '</pre>
                     <pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>
                     <h3><a href="javascript:window.history.back();">&larr;</a></h3>
                     <p><code>' . htmlspecialchars($uuid ?? '') . '</code></p>' :
                    '<h2><br /><br />Try again later</h2>
                     <h3><a href="javascript:window.history.back();">&larr;</a></h3>
                     <p><code>' . htmlspecialchars($uuid ?? '') . '</code></p>'
                ) .
            '</body>
            </html>';
            $done = sprintf('%01.5f', microtime(true) - $time);
            $res = new Response(
                (int)$code,
                $html,
                [
                    'X-Log' => (string)str_replace(["\r","\n"], ' ', $e->getMessage()),
                    'X-Request-UUID' => $uuid ?? '',
                    'X-Request-Time' => $done,
                    'X-User' => $user ?? '',
                    'X-Username' => $username ?? ''
                ]
            );
        }
        if ($res->hasHeader('X-Log') && $res->getHeaderLine('X-Log') === 'no') {
            return $res
                ->withoutHeader('X-Log')
                ->withoutHeader('X-Client-IP')
                ->withoutHeader('X-Request-Time')
                ->withoutHeader('X-User')
                ->withoutHeader('X-Username');
        }

        $msg = '[' . $req->getMethod() . '] ' . '/' . trim($req->getUrl()->getRealPath(), '/');
        $lvl = 'debug';

        /**
         * @psalm-suppress TypeDoesNotContainType,NoValue
         * @phpstan-ignore-next-line
         */
        if ($this->lastNotice !== '' && !$res->hasHeader('X-Log')) {
            $res = $res->withHeader('X-Log', $this->lastNotice);
        }

        if ($res->hasHeader('X-Log')) {
            $log = $res->getHeaderLine('X-Log');
            $matches = [];
            preg_match_all('(IDS|CSRF|CSP|ECT|XSS|RATE)', $log, $matches);
            if (count($matches[0])) {
                foreach ($matches[0] as $keyword) {
                    $msg = '[' . $keyword . '] ' . $msg;
                }
            }
            $log = trim(preg_replace(['(IDS|CSRF|CSP|ECT|XSS|RATE|CORS)', '(\s+)'], ['', ' '], $log) ?? '');
            $msg .= ' ' . $log;
        }
        if ($res->getStatusCode() >= 500) { // server errors
            $msg = '[' . $res->getStatusCode() . '] ' . $msg;
            $lvl = 'error';
        } elseif ($res->getStatusCode() >= 400) { // user errors
            $msg = '[' . $res->getStatusCode() . '] ' . $msg;
            $lvl = 'warning';
        } elseif (strlen($this->lastNotice)) {
            $lvl = 'notice';
        } elseif ($res->hasHeader('X-Log')) {
            $lvl = strpos($res->getHeaderLine('X-Log'), 'Exception: ') === 0 ? 'error' : 'notice';
        } elseif (in_array(trim($req->getUrl()->getRealPath(), '/'), $this->alwaysLog)) {
            $lvl = 'notice';
        } elseif (in_array($req->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'])) { // changes to the state
            $lvl = 'info';
        } else { // everything else
            $lvl = 'debug';
        }

        if ($lvl !== 'debug') {
            $rq = $this->serializeRequest($req);
            $rs = $this->serializeResponse($res);
            $time = time();
            // filesystem storage
            if ($this->storage !== 'DATABASE') {
                $temp = explode("\r\n\r\n", $rq);
                $rq = $temp[0] . "\r\n\r\n" . "*** SKIPPED ***";
                $uuid = $res->getHeaderLine('X-Request-UUID');
                if (isset($this->storage) && strlen($this->storage) && isset($temp[1]) && !empty($temp[1]) && $uuid) {
                    $path = rtrim($this->storage, '/') .
                        '/' . date('Y', $time) . '/' . date('m', $time) .  '/' . date('d', $time);
                    if (!is_dir($path)) {
                        @mkdir($path, 0777, true);
                    }
                    file_put_contents($path . '/' . $uuid . '.req', $temp[1]);
                }
                $temp = explode("\r\n\r\n", $rs);
                $rs = $temp[0] . "\r\n\r\n" . "*** SKIPPED ***";
                if (
                    isset($this->storage) &&
                    strlen($this->storage) &&
                    isset($temp[1]) &&
                    !empty($temp[1]) &&
                    $uuid
                ) {
                    $path = rtrim($this->storage, '/') .
                        '/' . date('Y', $time) . '/' . date('m', $time) .  '/' . date('d', $time);
                    if (!is_dir($path)) {
                        @mkdir($path, 0777, true);
                    }
                    file_put_contents($path . '/' . $uuid . '.res', $temp[1]);
                }
            }
            $temp = $res->getHeader('X-Context');
            $context = [];
            $cnt = 0;
            foreach ($temp as $line) {
                if (preg_match('(^[A-Z_-]+: )', $line)) {
                    $line = explode(': ', $line, 2);
                    $context[$line[0]] = $line[1];
                } else {
                    $context['context_' . ($cnt++)] = $line;
                }
            }
            $context = array_merge($this->context, $context);
            if ($req->hasCertificate()) {
                $context['SSL_CLIENT_M_SERIAL'] = $req->getCertificateNumber();
                if ($this->certificates && ($cert = $req->getCertificate())) {
                    $file = $req->getCertificateNumber() . '_' . md5($cert);
                    if (!is_file($this->certificates . '/' . $file)) {
                        file_put_contents($this->certificates . '/' . $file, $cert);
                    }
                    $context['SSL_CLIENT_M_SERIAL_FILE'] = $file;
                }
            }
            if ($lastException) {
                $context['stacktrace'] = $lastException->getTraceAsString();
            }
            $this->dbc->table('log')->insert([
                'created' => date('Y-m-d H:i:s', $time),
                'lvl' => $lvl,
                'message' => $msg,
                'context' => json_encode(
                    $context,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                ),
                'request' => $rq,
                'response' => $rs,
                'ip' => (string)$req->getAttribute('client-ip'),
                'usr' => $res->getHeaderLine('X-User') ? $res->getHeaderLine('X-User') : null,
                'usr_name' => $res->getHeaderLine('X-Username') ? $res->getHeaderLine('X-Username') : null
            ]);
        }
        $this->log->reset();
        return $res
            ->withoutHeader('X-Context')
            ->withoutHeader('X-Log')
            ->withoutHeader('X-Client-IP')
            ->withoutHeader('X-Request-Time')
            ->withoutHeader('X-User')
            ->withoutHeader('X-Username');
    }
    protected function serializeRequest(Request $req): string
    {
        $rq = explode("\r\n\r\n", RequestSerializer::toString($req))[0];
        if (count($req->getPost())) {
            $rq .= "\r\n\r\n" .
            json_encode(
                $req->getPost(),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
            ) .
            "\r\n";
        }
        $rq = preg_replace(
            '(Cookie\:[^\n]*\n)i',
            'Cookie: *** PRIVATE ***' . "\r\n",
            $rq
        ) ?: throw new \RuntimeException();
        $rq = preg_replace(
            '(Authorization\:[^\n]*\n)i',
            'Authorization: *** PRIVATE ***' . "\r\n",
            $rq
        ) ?: throw new \RuntimeException();
        if (
            $req->getMethod() === 'POST' &&
            in_array($req->getUrl()->getRealPath(), $this->privateRequests)
        ) {
            $temp = explode("\r\n\r\n", $rq);
            $rq = $temp[0];
            if (isset($temp[1]) && !empty($temp[1])) {
                $rq .= "\r\n\r\n" . "*** PRIVATE ***";
            }
        }
        return $rq;
    }
    protected function serializeResponse(Response $res): string
    {
        if ($res->hasHeader('X-Private')) {
            return '*** PRIVATE ***';
        }
        $rs = ResponseSerializer::toString($res) . "\r\n";
        $rs = preg_replace('(Set-Cookie\:[^\n]*\n)i', 'Set-Cookie: *** PRIVATE ***' . "\r\n", $rs) ?? '';
        return $rs;
    }
}
