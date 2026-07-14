<?php

declare(strict_types=1);

namespace webadmin\modules\common\dashboard;

use vakata\http\Response as Response;
use vakata\http\Uri as Url;
use vakata\views\Views;
use vakata\config\Config;
use vakata\user\User;
use vakata\user\UserManagementInterface as UMI;

class DashboardController
{
    public function __construct(Views $views)
    {
        $views->addFolder('dashboard', __DIR__ . '/views');
    }

    public function index(Response $res, Url $url, Views $views, User $user, UMI $usrm, Config $config): Response
    {
        $errors = [];
        if ($user->hasPermission('dashboard/errors')) {
            if ($config->get('DEBUG')) {
                $errors[] = 'turn_off_debug';
            }
            if (strpos($url->getSegment('base'), '/admin/') !== false) {
                $errors[] = 'rename_admin_folder';
            }
            foreach (['admin', 'administrator', 'demo', 'test'] as $username) {
                try {
                    $usrm->getUserByProviderID('PasswordDatabase', $username);
                    $errors[] = 'remove_admin_user';
                    break;
                } catch (\Exception) {
                }
            }
        }

        return $res->setBody(
            $views->render('dashboard::index', [
                'errors' => $errors
            ])
        );
    }
}
