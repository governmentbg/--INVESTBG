<?php

declare(strict_types=1);

namespace webadmin\middleware;

use vakata\http\Request;
use vakata\http\Response;
use vakata\cache\CacheInterface;
use vakata\config\Config;
use vakata\database\DBInterface;

class Health
{
    protected Config $config;
    protected DBInterface $db;
    protected CacheInterface $cache;
    protected string $path;

    public function __construct(Config $config, DBInterface $db, CacheInterface $cache, string $path)
    {
        $this->config = $config;
        $this->db = $db;
        $this->cache = $cache;
        $this->path = $path;
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        if (trim($req->getUrl()->getRealPath(), '/') !== $this->path || $req->getMethod() !== 'GET') {
            return $next($req);
        }

        if (
            $this->config->getString('STATUS_CHECKER_USER') &&
            $this->config->getString('STATUS_CHECKER_PASS')
        ) {
            $auth = $req->getAuthorization();
            if (
                !isset($auth['username']) || !isset($auth['password']) ||
                $auth['username'] !== $this->config->getString('STATUS_CHECKER_USER') ||
                $auth['password'] !== $this->config->getString('STATUS_CHECKER_PASS')
            ) {
                return (new Response(401))->withHeader('WWW-Authenticate', 'Basic realm="_status"');
            }
        }
        try {
            $status = $this->checks();
            return (new Response(in_array(false, $status, true) ? 500 : 200))
                ->setContentTypeByExtension('json')
                ->setBody(json_encode($status) ?: '{}');
        } catch (\Throwable $e) {
            return (new Response(500))
                ->setContentTypeByExtension('json')
                ->setBody(json_encode(['error' => 'unknown']) ?: '{}');
        }
    }
    protected function checks(): array
    {
        $result = [];

        foreach (
            [
                'STORAGE_UPLOADS',
                'STORAGE_CACHE',
                'STORAGE_SESSION',
                'STORAGE_LOG',
                'STORAGE_TMP',
                'STORAGE_INTL',
                'STORAGE_DATABASE',
                'STORAGE_MAIL',
                'STORAGE_REQ',
                'STORAGE_CERTIFICATES',
                'STORAGE_KEYS'
            ] as $const
        ) {
            if ($dir = $this->config->getString($const)) {
                if ($const === 'STORAGE_SESSION' && ($dir === 'DATABASE' || $dir === 'CACHE')) {
                    continue;
                } elseif ($const === 'STORAGE_REQ' && $dir === 'DATABASE') {
                    continue;
                } elseif ($const === 'STORAGE_MAIL' && $dir === 'DATABASE') {
                    continue;
                } elseif ($const === 'STORAGE_CACHE' && $this->config->getString('CACHE') !== 'file') {
                    continue;
                } elseif ($const === 'STORAGE_UPLOADS' && $dir === 'DATABASE') {
                    continue;
                } elseif ($const === 'STORAGE_LOG' && $this->config->getString('LOG') !== 'file') {
                    continue;
                }
                $result[$const] = (is_dir($dir) && is_writeable($dir));
            }
        }

        if ($this->config->getString('DATABASE')) {
            try {
                $this->db->one("SELECT 1 FROM migrations");
                $result['DATABASE_READ'] = true;
            } catch (\Exception) {
                $result['DATABASE_READ'] = false;
            }
            try {
                if (strpos($this->config->getString('DATABASE'), 'mysql') === 0) {
                    $mysqlVer = $this->db->one("SELECT VERSION()");
                    $parts = explode('.', $mysqlVer);
                    if ((int)$parts[0] > 5 || (int)$parts[1] >= 7) {
                        $result['MYSQL_VERSION'] = true;
                    } else {
                        $result['MYSQL_VERSION'] = false;
                    }
                }
                if (strpos($this->config->getString('DATABASE'), 'postgre') === 0) {
                    $pgVer = $this->db->one("SELECT VERSION()");
                    $parts = explode(' ', $pgVer);
                    if ((int)$parts[1] >= 14) {
                        $result['PG_VERSION'] = true;
                    } else {
                        $result['PG_VERSION'] = false;
                    }
                }
                if (strpos($this->config->getString('DATABASE'), 'oracle') === 0) {
                    $oraVer = $this->db->one('SELECT * FROM v$version WHERE banner LIKE \'Oracle%\'');
                    $parts = explode(' ', $oraVer);
                    if ((int)$parts[2] > 12) {
                        $result['ORA_VERSION'] = true;
                    } else {
                        $result['ORA_VERSION'] = true;
                    }
                }
            } catch (\Exception) {
                $result['DATABASE_VERSION'] = false;
            }
        }

        if ($this->config->getString('CACHE')) {
            $tmp = date('c');
            try {
                $this->cache->set('__health_checker', $tmp, 5);
                $result['CACHE'] = $tmp === $this->cache->get('__health_checker');
            } catch (\Throwable $e) {
                $result['CACHE'] = false;
            }
        }

        if (!$this->config->getBool('MAILQUEUE') && $this->config->getString('SMTP_CONNECTION')) {
            try {
                $mailer = new \vakata\mail\driver\SMTPSender(
                    $this->config->getString('SMTP_CONNECTION'),
                    $this->config->get('SMTP_USER'),
                    $this->config->get('SMTP_PASSWORD')
                );
                $mailer->connect();
                $result['SMTP'] = true;
            } catch (\Exception $e) {
                $result['SMTP'] = false;
            }
        }

        $phpVer = phpversion();
        $parts = explode('.', (string)$phpVer);
        if ((int)$parts[0] > 7 || (int)$parts[1] >= 3) {
            $result['PHP_VERSION'] = true;
        } else {
            $result['PHP_VERSION'] = true;
        }

        $phpExt = get_loaded_extensions();
        if (in_array('gd', $phpExt) || in_array('imagick', $phpExt)) {
            $result['PHP_IMAGES'] = true;
        } else {
            $result['PHP_IMAGES'] = false;
        }

        foreach (['zip', 'SimpleXML', 'mbstring', 'iconv', 'openssl'] as $ext) {
            if (in_array($ext, $phpExt)) {
                $result['PHP_' . strtoupper($ext)] = true;
            } else {
                $result['PHP_' . strtoupper($ext)] = false;
            }
        }

        if (isset($_SERVER["SERVER_SOFTWARE"]) && strpos(strtolower($_SERVER["SERVER_SOFTWARE"]), 'apache/') === 0) {
            list($name, $version) = explode('/', $_SERVER["SERVER_SOFTWARE"], 2);
            $parts = explode('.', $version);
            if ((int)$parts[0] > 2 || (int)$parts[1] >= 4) {
                $result['APACHE'] = true;
            } else {
                $result['APACHE'] = false;
            }
        }

        if (in_array('aes-256-gcm', openssl_get_cipher_methods()) && in_array('sha256', hash_algos())) {
            $result['CRYPTO'] = true;
        } else {
            $result['CRYPTO'] = false;
        }

        return $result;
    }
}
