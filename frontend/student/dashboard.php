<?php
declare(strict_types=1);

require_once __DIR__ . '/../../backend/includes/session.php';
require_once __DIR__ . '/../../backend/includes/database.php';

startAppSession();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: /login.php');
    exit;
}

function escapeStudent(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$userId = (int) $_SESSION['user_id'];
$profile = [
    'full_name' => (string) ($_SESSION['full_name'] ?? 'Student'),
    'email' => null,
    'phone' => null,
];
$totalListings = null;
$inquiryCount = null;
$tenancyCount = null;
$databaseUnavailable = false;

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT full_name, email, phone FROM `User` WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) {
        $profile = $row;
    }

    // Only count known relationships from the database team's foreign keys.
    // Availability filtering needs the BoardingListing columns to be confirmed.
    $totalListings = (int) $pdo->query('SELECT COUNT(*) FROM `BoardingListing`')->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM `Inquiry` WHERE student_user_id = ?');
    $stmt->execute([$userId]);
    $inquiryCount = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM `Tenancy` WHERE student_user_id = ?');
    $stmt->execute([$userId]);
    $tenancyCount = (int) $stmt->fetchColumn();
} catch (Throwable $exception) {
    error_log('UniStay student dashboard data error: ' . get_class($exception));
    $databaseUnavailable = true;
}

$name = escapeStudent((string) ($profile['full_name'] ?: 'Student'));
$email = escapeStudent((string) ($profile['email'] ?: 'Not provided'));
$phone = escapeStudent((string) ($profile['phone'] ?: 'Not provided'));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Dashboard - UniStay</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a href="/student/dashboard.php" class="brand" aria-label="UniStay Student Dashboard">
            <span class="brand-mark">U</span><span class="brand-name">UniStay</span>
        </a>
        <nav aria-label="Student navigation">
            <p class="nav-label">Workspace</p>
            <a href="/student/dashboard.php" class="nav-item active" aria-current="page">▦ &nbsp; Overview</a>
            <span class="nav-item disabled" title="Boarding search is the next feature">⌂ &nbsp; Find boarding</span>
            <span class="nav-item disabled" title="Inquiry tracking is the next feature">✉ &nbsp; My inquiries</span>
            <span class="nav-item disabled" title="Tenancy tracking is the next feature">▣ &nbsp; My tenancy</span>
            <a href="/logout.php" class="nav-item">↪ &nbsp; Sign out</a>
        </nav>
        <div class="sidebar-help"><strong>Student workspace</strong>Find suitable accommodation, contact owners, and manage your tenancy from your own account.</div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div><p class="eyebrow">Student accommodation</p><h1>Student overview</h1></div>
            <span class="role-badge">Student account</span>
        </header>

        <section class="hero">
            <div>
                <h2>Hey <?= $name ?>, welcome home!</h2>
                <p>Find a place that fits your student life, contact the property owner, and keep track of your accommodation journey.</p>
            </div>
            <span class="hero-symbol" aria-hidden="true">⌂</span>
        </section>

        <?php if ($databaseUnavailable): ?>
            <p class="alert" role="status">Some dashboard information is unavailable. Your account is still signed in; please try refreshing later.</p>
        <?php endif; ?>

        <div class="section-heading"><h2>Your overview</h2><span></span></div>
        <div class="stat-grid">
            <div class="stat-card"><p class="stat-label">Boarding listings</p><div class="stat-number"><?= $totalListings === null ? '—' : number_format($totalListings) ?></div><p class="stat-hint">All listings, regardless of availability</p></div>
            <div class="stat-card"><p class="stat-label">My inquiries</p><div class="stat-number"><?= $inquiryCount === null ? '—' : number_format($inquiryCount) ?></div><p class="stat-hint">Messages you sent to property owners</p></div>
            <div class="stat-card"><p class="stat-label">My tenancies</p><div class="stat-number"><?= $tenancyCount === null ? '—' : number_format($tenancyCount) ?></div><p class="stat-hint">Tenancy records linked to your account</p></div>
        </div>

        <div class="section-heading"><h2>Your next steps</h2><span>Feature rollout</span></div>
        <div class="panel-grid">
            <section class="panel">
                <h3>Find your next place</h3>
                <div class="step-list">
                    <div class="step"><span class="step-num">1</span><div><strong>Explore boardings</strong><small>Search by university, rent, and location once browsing is connected.</small></div></div>
                    <div class="step"><span class="step-num">2</span><div><strong>Ask the owner</strong><small>Send an inquiry directly to an owner using your own student account.</small></div></div>
                    <div class="step"><span class="step-num">3</span><div><strong>Manage your stay</strong><small>Track the owner's response and any confirmed tenancy.</small></div></div>
                </div>
            </section>
            <section class="panel">
                <h3>My account</h3>
                <div class="profile-row"><span>Name</span><strong><?= $name ?></strong></div>
                <div class="profile-row"><span>Email</span><strong><?= $email ?></strong></div>
                <div class="profile-row"><span>Phone</span><strong><?= $phone ?></strong></div>
                <div class="profile-row"><span>Role</span><strong>Student</strong></div>
            </section>
        </div>
        <p class="footer">UniStay · Student accommodation made simpler</p>
    </main>
</div>
</body>
</html>
