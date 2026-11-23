<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

log_audit('auth.logout', 'user', isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null);
logout_user();
