<?php
declare(strict_types=1);
?>
        </main>
        <footer class="flex flex-col gap-2 border-t border-line px-4 py-5 text-xs text-muted sm:flex-row sm:items-center sm:justify-between lg:px-8">
            <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> — Admin Panel v<?= e(APP_VERSION) ?></p>
            <p class="flex items-center gap-1.5"><?= icon('shield', 'h-3.5 w-3.5') ?> Semua aksi admin tercatat di audit log.</p>
        </footer>
    </div>
</div>

<div id="modal-root"></div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
<?php foreach (($pageScripts ?? []) as $script): ?>
    <script src="<?= e(asset('js/' . $script)) ?>" defer></script>
<?php endforeach; ?>
<?php if (isset($inlineScript)): ?>
<script><?= $inlineScript ?></script>
<?php endif; ?>
</body>
</html>
