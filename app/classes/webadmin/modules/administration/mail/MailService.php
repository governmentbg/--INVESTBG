<?php

declare(strict_types=1);

namespace webadmin\modules\administration\mail;

use DateTime;
use vakata\config\Config;
use vakata\user\User;
use vakata\mail\Mail;

class MailService
{
    protected User $user;
    protected string $storage;

    public function __construct(User $user, Config $config)
    {
        $this->user = $user;
        $this->storage = $config->get('STORAGE_MAIL');
    }
    public function list(DateTime $date): array
    {
        $lst = [];
        $dir = realpath($this->storage) . '/' . $date->format('Y-m-d');
        if (is_dir($dir)) {
            $files = scandir($dir);
            if (!$files) {
                $files = [];
            }
            foreach ($files as $file) {
                if (!is_file($dir . '/' . $file) || !strpos($file, '_')) {
                    continue;
                }
                $stamp = explode('_', $file, 2)[0] ?? 0;
                try {
                    $mail = Mail::fromString(file_get_contents($dir . '/' . $file) ?: '');
                    $lst[] = [
                        'time' => date('H:i:s', (int)$stamp),
                        'file' => $date->format('Y-m-d') . '$' . str_replace('.txt', '', $file),
                        'recv' => implode(', ', array_merge(
                            $mail->getTo(true),
                            $mail->getCc(true),
                            $mail->getBcc(true)
                        )),
                        'subject' => $mail->getSubject()
                    ];
                } catch (\Exception) {
                    // invalid email (manually created file / dummy?)
                }
            }
        }
        uasort($lst, function (array $a, array $b) {
            return $b['time'] <=> $a['time'];
        });
        return $lst;
    }
    public function mail(string $file): ?string
    {
        if (!strpos($file, '$')) {
            return null;
        }
        list($date, $file) = explode('$', $file, 2);
        $mail = realpath($this->storage) . '/' . $date . '/' . $file . '.txt';
        if (!is_file($mail)) {
            return null;
        }
        $data = file_get_contents($mail);
        return $data !== false ? $data : null;
    }
}
