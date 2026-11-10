<?php
declare(strict_types=1);
require_once __DIR__ . '/unistay_module.php';

/** Keep account editing independent from owner/student tenancy actions. */
function profile_role_and_id(): array
{
    $role = (string)($_SESSION['role'] ?? '');
    if (!in_array($role, ['student', 'owner'], true)) {
        require_once __DIR__ . '/auth.php';
        if (empty($_SESSION['user_id'])) {
            header('Location: /login.php', true, 303);
            exit;
        }
        http_response_code(403);
        exit('This profile page is for student and owner accounts.');
    }
    return [$role, module_require($role)];
}

function profile_display_role(string $role): string
{
    return $role === 'owner' ? 'Boarding Owner' : 'Student';
}

function profile_validate_name(mixed $value): string
{
    $name = trim((string)$value);
    if (strlen($name) < 2 || strlen($name) > 255 || preg_match('/[\x00-\x1F\x7F]/', $name)) {
        throw new InvalidArgumentException('Enter a name of 2–255 characters without control characters.');
    }
    return $name;
}

function profile_validate_phone(mixed $value): ?string
{
    $phone = trim((string)$value);
    if ($phone === '') return null;
    if (strlen($phone) > 20 || !preg_match('/^[+0-9() \-]{7,20}$/D', $phone)
        || preg_match_all('/[0-9]/', $phone) < 7) {
        throw new InvalidArgumentException('Enter a valid phone number (7–20 characters) or leave it blank.');
    }
    return $phone;
}
