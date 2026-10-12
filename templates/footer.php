<?php
/**
 * NusaPOS - Layout Footer
 */
declare(strict_types=1);

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
    <!-- Global Toast Container -->
    <div id="toast-container" class="fixed top-5 right-5 z-[9999] flex flex-col gap-2 pointer-events-none"></div>

    <!-- Scripts -->
    <script src="public/js/app.js"></script>

    <?php if ($flashSuccess): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            showToast(<?= json_encode($flashSuccess) ?>, 'success');
        });
    </script>
    <?php endif; ?>

    <?php if ($flashError): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            showToast(<?= json_encode($flashError) ?>, 'error');
        });
    </script>
    <?php endif; ?>

    <?php if (isset($extraScripts)): ?>
        <?= $extraScripts ?>
    <?php endif; ?>
</body>
</html>
