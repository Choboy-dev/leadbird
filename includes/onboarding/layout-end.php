        </div>
    </main>
</div>
<script src="<?= e($appUrl) ?>/assets/js/modal.js?v=2"></script>
<script src="<?= e($appUrl) ?>/assets/js/dropdown.js?v=6"></script>
<script src="<?= e($appUrl) ?>/assets/js/onboarding.js?v=2"></script>
<?php require __DIR__ . '/../session-idle.php'; ?>
<?php foreach ($extraScripts ?? [] as $script): ?>
    <script src="<?= e($appUrl) ?>/assets/js/<?= e($script) ?>"></script>
<?php endforeach; ?>
<?php
$skipSiteAssistant = true;
require __DIR__ . '/../site-assistant/embed.php';
?>
</body>
</html>
