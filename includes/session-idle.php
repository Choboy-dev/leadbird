<?php

declare(strict_types=1);

$sessionTimeout = SessionAuth::timeoutConfig();
$idleMinutes = (int) $sessionTimeout['idle_timeout_minutes'];
$warningSeconds = (int) $sessionTimeout['idle_warning_seconds'];
?>
<script>
    window.LeadBirdSession = {
        touchUrl: <?= json_encode(app_route('auth/session-touch'), JSON_THROW_ON_ERROR) ?>,
        logoutUrl: <?= json_encode(app_route('auth/logout', ['reason' => 'idle']), JSON_THROW_ON_ERROR) ?>,
        idleMs: <?= $idleMinutes * 60 * 1000 ?>,
        warningMs: <?= $warningSeconds * 1000 ?>,
        idleMinutes: <?= $idleMinutes ?>,
    };
</script>
<script src="<?= e($appUrl) ?>/assets/js/dashboard-idle.js?v=1"></script>
