        </div>
        </main>
    </div>
</div>
<?php if (!empty($dashboardPortalPartial) && is_string($dashboardPortalPartial)): ?>
    <?php require $dashboardPortalPartial; ?>
<?php endif; ?>
<script src="<?= e($appUrl) ?>/assets/js/modal.js?v=2"></script>
<script src="<?= e($appUrl) ?>/assets/js/dropdown.js?v=6"></script>
<script src="<?= e($appUrl) ?>/assets/js/dashboard.js?v=11"></script>
<?php require __DIR__ . '/../session-idle.php'; ?>
<?php foreach ($extraScripts ?? [] as $script): ?>
    <script src="<?= e($appUrl) ?>/assets/js/<?= e($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
