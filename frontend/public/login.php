<?php
declare(strict_types=1);

// The one public entry point for UniStay login.
// The UI template is included rather than exposed as another login route.
define('UNISTAY_LOGIN_ENTRY', true);
require_once __DIR__ . '/../src/pages/login.php';
