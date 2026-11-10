<?php
declare(strict_types=1);

require_once __DIR__ . '/../../backend/includes/session.php';
require_once __DIR__ . '/../../backend/includes/database.php';

startAppSession();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: /login.php');
    exit;
}

function escapeOwner(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$userId = (int) $_SESSION['user_id'];
$profile = [
    'full_name' => (string) ($_SESSION['full_name'] ?? 'Property Owner'),
    'email' => null,
    'phone' => null,
];
$propertyCount = null;
$databaseUnavailable = false;

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT full_name, email, phone FROM `User` WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) {
        $profile = $row;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM `BoardingListing` WHERE owner_id = ?');
    $stmt->execute([$userId]);
    $propertyCount = (int) $stmt->fetchColumn();
} catch (Throwable $exception) {
    error_log('UniStay owner dashboard data error: ' . get_class($exception));
    $databaseUnavailable = true;
}

$name = escapeOwner((string) ($profile['full_name'] ?: 'Property Owner'));
$email = escapeOwner((string) ($profile['email'] ?: 'Not provided'));
$phone = escapeOwner((string) ($profile['phone'] ?: 'Not provided'));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Owner Dashboard - UniStay</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a href="/owner/dashboard.php" class="brand" aria-label="UniStay Owner Dashboard">
            <span class="brand-mark">U</span><span class="brand-name">UniStay</span>
        </a>
        <nav aria-label="Owner navigation">
            <p class="nav-label">Workspace</p>
            <a href="/owner/dashboard.php" class="nav-item active" aria-current="page">▦ &nbsp; Overview</a>
            <span class="nav-item disabled" title="Property management is the next feature">⌂ &nbsp; My properties</span>
            <span class="nav-item disabled" title="Inquiry management is the next feature">✉ &nbsp; Inquiries</span>
            <span class="nav-item disabled" title="Tenancy management is the next feature">▣ &nbsp; Tenancies</span>
            <a href="/logout.php" class="nav-item">↪ &nbsp; Sign out</a>
        </nav>
        <div class="sidebar-help"><strong>Owner workspace</strong>Manage your properties, respond to student inquiries, and confirm tenants once these features are connected.</div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div><p class="eyebrow">Property management</p><h1>Owner overview</h1></div>
            <span class="role-badge">Property owner</span>
        </header>

        <section class="hero">
            <div>
                <h2>Welcome back, <?= $name ?>!</h2>
                <p>Here's your property workspace. You'll manage boarding listings and review inquiries from registered students here.</p>
            </div>
            <span class="hero-symbol" aria-hidden="true">⌂</span>
        </section>

        <?php if ($databaseUnavailable): ?>
            <p class="alert" role="status">Some dashboard information is unavailable. Your account is still signed in; please try refreshing later.</p>
        <?php endif; ?>

        <div class="section-heading"><h2>At a glance</h2><span>Live data from TiDB</span></div>
        <div class="stat-grid">
            <div class="stat-card"><p class="stat-label">My boarding listings</p><div class="stat-number"><?= $propertyCount === null ? '—' : number_format($propertyCount) ?></div><p class="stat-hint">Properties linked to your account</p></div>
            <div class="stat-card"><p class="stat-label">Account type</p><div class="stat-number" style="font-size:25px">Owner</div><p class="stat-hint">Your signed-in account role</p></div>
            <div class="stat-card"><p class="stat-label">Inquiry management</p><div class="stat-number" style="font-size:24px">Next</div><p class="stat-hint">Awaiting the Inquiry table integration</p></div>
        </div>

        <div class="section-heading"><h2>Your workspace</h2><span>Feature rollout</span></div>
        <div class="panel-grid">
            <section class="panel">
                <h3>How ownership works</h3>
                <div class="step-list">
                    <div class="step"><span class="step-num">1</span><div><strong>Publish your boarding</strong><small>Add rent, location, facilities, and photos when listing management is enabled.</small></div></div>
                    <div class="step"><span class="step-num">2</span><div><strong>Respond to inquiries</strong><small>Interested students contact you through UniStay. Their accounts remain independent of yours.</small></div></div>
                    <div class="step"><span class="step-num">3</span><div><strong>Confirm a tenancy</strong><small>Approve a student's rental request and track the tenancy once agreed.</small></div></div>
                </div>
            </section>
            <section class="panel">
                <h3>My account</h3>
                <div class="profile-row"><span>Name</span><strong><?= $name ?></strong></div>
                <div class="profile-row"><span>Email</span><strong><?= $email ?></strong></div>
                <div class="profile-row"><span>Phone</span><strong><?= $phone ?></strong></div>
                <div class="profile-row"><span>Role</span><strong>Property owner</strong></div>
            </section>
        </div>
        <p class="footer">UniStay · Student accommodation made simpler</p>
    </main>
</div>
</body>
</html>
