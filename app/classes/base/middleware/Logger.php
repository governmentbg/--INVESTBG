<?php

declare(strict_types=1);

namespace base\middleware;

use Monolog\Logger as Log;
use vakata\http\Request;
use vakata\http\Response;

class Logger
{
    protected Log $log;
    protected bool $debug;

    /**
     * @param Log $log
     * @param boolean $debug
     */
    public function __construct(
        Log $log,
        bool $debug = false
    ) {
        $this->log = $log;
        $this->debug = $debug;
    }

    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $lastNotice = '';
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use (&$lastNotice) {
            // do not touch errors where @ is used or that are not marked for reporting
            if ($errno === 0 || !($errno & error_reporting())) {
                return true;
            }
            // do not throw, only log "lightweight" errors
            if (in_array($errno, [ E_NOTICE, E_DEPRECATED, E_USER_NOTICE, E_USER_DEPRECATED ])) {
                $lastNotice = 'PHP Notice: ' . $errstr .
                    ($errfile && $errline ? ' in ' . $errfile . ' on line ' . $errline : '');
                $this->log->notice($lastNotice);
                return true;
            }
            // throw exception for all others
            throw new \ErrorException($errstr, $errno, $errno, $errfile, $errline);
        });
        try {
            $res = $next($req);
            if ($res->hasHeader('X-Log')) {
                $message = $res->getHeaderLine('X-Log');
                if (strpos($message, 'Exception: ') === 0) {
                    $this->log->error(explode('Exception: ', $message, 2)[1] ?? '');
                }
                $res->withoutHeader('X-Log');
            }
        } catch (\Throwable $e) {
            $message = $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
            $severity = $e instanceof \ErrorException ? $e->getSeverity() : E_ERROR;
            switch ($severity) {
                case E_ERROR:
                case E_RECOVERABLE_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                case E_PARSE:
                    $this->log->error($message);
                    break;
                case E_WARNING:
                case E_USER_WARNING:
                case E_CORE_WARNING:
                case E_COMPILE_WARNING:
                    $this->log->warning($message);
                    break;
                case E_NOTICE:
                case E_USER_NOTICE:
                    $this->log->notice($message);
                    break;
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                    $this->log->info($message);
                    break;
                default:
                    $this->log->critical($message);
                    break;
            }
            $code = $e->getCode() >= 200 && $e->getCode() <= 503 ? $e->getCode() : 500;
            $html = '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>' . htmlspecialchars((string)$code) . '</title>
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
                     <h3><a href="javascript:window.history.back();">&larr;</a></h3>' :
                    '<h2><br /><br />Try again later</h2>
                     <h3><a href="javascript:window.history.back();">&larr;</a></h3>'
                ) .
            '</body>
            </html>';
            $res = new Response((int)$code, $html);
        }
        return $res;
    }
}
