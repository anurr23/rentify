<?php
declare(strict_types=1);
?>
<div id="modal-root"></div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php foreach (($pageScripts ?? []) as $script): ?>
    <script src="<?= e(asset('js/' . $script)) ?>" defer></script>
<?php endforeach; ?>
<?php if (isset($inlineScript)): ?>
<script><?= $inlineScript ?></script>
<?php endif; ?>
</body>
</html>
