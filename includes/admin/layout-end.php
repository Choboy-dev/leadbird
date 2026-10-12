        </div>
        </main>
    </div>
</div>
<script src="<?= e($appUrl) ?>/assets/js/modal.js?v=2"></script>
<script src="<?= e($appUrl) ?>/assets/js/dropdown.js?v=7"></script>
<script src="<?= e($appUrl) ?>/assets/js/dashboard.js?v=12"></script>
<script src="<?= e($appUrl) ?>/assets/js/admin-dashboard.js?v=1"></script>
<?php require __DIR__ . '/../session-idle.php'; ?>
<?php foreach ($extraScripts ?? [] as $script): ?>
    <script src="<?= e($appUrl) ?>/assets/js/<?= e($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
