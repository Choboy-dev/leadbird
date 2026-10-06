<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (!AdminAuth::isImpersonating()) {
    redirect(app_route('admin'));
}

(new AdminService())->stopImpersonation();
