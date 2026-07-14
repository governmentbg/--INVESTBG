<?php

declare(strict_types=1);

namespace webadmin\modules\administration\errors;

use DateTime;
use vakata\config\Config;
use vakata\user\User;

class ErrorsService
{
    protected User $user;
    protected ?string $storage       = null;
    protected ?string $storagePublic = null;

    public function __construct(User $user, Config $config)
    {
        $this->user = $user;
        /** @psalm-suppress PossiblyFalsePropertyAssignmentValue */
        $this->storage = str_contains($config->get('LOG'), 'file') && realpath($config->get('STORAGE_LOG')) ?
            realpath($config->get('STORAGE_LOG')) :
            null;
        $this->storagePublic = $config->get('STORAGE_LOG_PUBLIC') && realpath($config->get('STORAGE_LOG_PUBLIC', '')) ?
            (realpath($config->get('STORAGE_LOG_PUBLIC')) ?: '') :
            null;
    }

    /**
     * @param DateTime      $date
     * @param bool $admin
     * @param bool $public
     *
     * @return array<string,array{level:string,count:int,time:int,text:string,file:string,line:string}>
     */
    public function list(DateTime $date, bool $admin = true, bool $public = true): array
    {
        $errors = [];
        if ($admin) {
            $errors = array_merge($errors, $this->getStorageErrors($this->storage, $date));
        }
        if ($public) {
            $errors = array_merge($errors, $this->getStorageErrors($this->storagePublic, $date));
        }

        uasort($errors, function (array $a, array $b) {
            return $b['time'] <=> $a['time'];
        });

        return $errors;
    }

    public function getStorageErrors(?string $storage, DateTime $date): array
    {
        if (!$storage) {
            return [];
        }
        $errors = [];
        $file = $storage . '/' . $date->format('Y') . '/' . $date->format('m.d') . '.log';
        if (is_file($file)) {
            $handle = fopen($file, 'r') ?: throw new \RuntimeException();
            while (($row = fgets($handle))) {
                $row = trim($row, "\r\n");
                preg_match(
                    '(' .
                        '\[(?P<date>[^\]]+)\] .*?\.(?P<level>[a-z]+): (?P<error>[^\[\{]+) ' .
                        '(?P<context>[\[\{].*[\]\}]) (?P<extra>[\[\{].*[\]\}])' .
                    ')i',
                    $row,
                    $data
                );
                $ekey = md5($data['error'] ?? '');
                if (isset($errors[$ekey])) {
                    if (isset($data['date']) && strtotime($data['date'])) {
                        $errors[$ekey]['time'] = strtotime($data['date']);
                    }
                    $errors[$ekey]['count']++;
                    $errors[$ekey]['context'] = $data['context'] ?? $errors[$ekey]['context'] ?? '{}';
                } else {
                    $temp = explode(' in ', str_replace('[]', '', $data['error'] ?? ''), 2);
                    $file = explode(' on line ', $temp[1] ?? '');
                    $line = $file[1] ?? '';
                    $file = $file[0] ?? '';
                    $text = $temp[0];
                    $dt = strtotime($data['date'] ?? '');
                    if (!$dt) {
                        $dt = time();
                    }
                    $errors[$ekey] = [
                        'level' => strtolower($data['level'] ?? 'error'),
                        'count' => 1,
                        'time'  => $dt,
                        'text'  => $text,
                        'file'  => $file,
                        'line'  => $line,
                        'context' => $data['context'] ?? '{}'
                    ];
                    if ($errors[$ekey]['level'] === 'critical') {
                        $errors[$ekey]['level'] = 'error';
                    }
                }
            }
            fclose($handle);
        }

        return $errors;
    }
}
