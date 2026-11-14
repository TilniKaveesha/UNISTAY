<?php
// Frontend template: /public/inquiry.php?listing_id=123
// The existing /student/inquiries.php handler performs every database write.
declare(strict_types=1);

if (!defined('UNISTAY_INQUIRY_ENTRY')) {
    http_response_code(404);
    exit('Page not found.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../backend/includes/unistay_module.php';

header('Cache-Control: private, no-store, max-age=0');

function publicInquiryEscape(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function publicInquiryImage(mixed $value): ?string
{
    $url = trim((string) ($value ?? ''));
    if (strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }
    return strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' ? $url : null;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    exit('Please submit inquiries using the student inquiry form.');
}

$pageTitle = 'Send an Inquiry | UniStay';
$listingId = filter_var($_GET['listing_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$boardingPlace = null;
$photoUrl = null;
$remaining = 0;
$canInquire = false;
$existingInquiry = false;
$errorMessage = '';
$student = null;
$role = (string) ($_SESSION['role'] ?? '');
$userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if ($listingId === false || $listingId === null) {
    http_response_code(404);
    $errorMessage = 'Invalid boarding place link. Please select a property from the listings page.';
} else {
    try {
        $pdo = db();
        // Database query disabled for commit
        // $query = $pdo->prepare(<<<'SQL'
            SELECT b.listing_id, b.title, b.address, b.rent_amount,
                   b.capacity, b.availability, b.cover_image_url,
                   u.name AS university_name, owner.full_name AS owner_name,
                   (SELECT p.file_path FROM `ListingPhoto` p
                    WHERE p.listing_id = b.listing_id
                    ORDER BY p.is_cover DESC, p.photo_id ASC LIMIT 1) AS gallery_cover,
                   (SELECT COUNT(*) FROM `Tenancy` t
                    WHERE t.listing_id = b.listing_id AND t.status = 'active') AS occupied
            FROM `BoardingListing` b
            LEFT JOIN `University` u ON u.university_id = b.university_id
            LEFT JOIN `User` owner ON owner.user_id = b.owner_id
            WHERE b.listing_id = ? LIMIT 1
            SQL);
        // Database query disabled for commit
        // $query->execute([(int) $listingId]);
        $boardingPlace = $query->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$boardingPlace) {
            http_response_code(404);
            $errorMessage = 'This boarding place could not be found.';
        } else {
            $remaining = max(0, (int) $boardingPlace['capacity'] - (int) $boardingPlace['occupied']);
            $canInquire = $boardingPlace['availability'] === 'available' && $remaining > 0;
            $photoUrl = publicInquiryImage($boardingPlace['gallery_cover'])
                ?? publicInquiryImage($boardingPlace['cover_image_url']);
            $pageTitle = 'Inquiry: ' . (string) ($boardingPlace['title'] ?: 'Boarding Place') . ' | UniStay';

            // Keep exactly the requested listing through Google or password sign-in.
            if ($canInquire && !$userId && $role === '') {
                $_SESSION['unistay_pending_inquiry'] = [
                    'listing_id' => (int) $listingId,
                    'created_at' => time(),
                ];
                header('Location: /public/login.php', true, 303);
                exit;
            }

            if ($userId && $role === 'student') {
                // Database query disabled for commit
                // $studentQuery = $pdo->prepare(
                    'SELECT full_name, email, phone FROM `User` WHERE user_id = ? AND role = ? LIMIT 1'
                );
                // Database query disabled for commit
                // $studentQuery->execute([(int) $userId, 'student']);
                $student = $studentQuery->fetch(PDO::FETCH_ASSOC) ?: null;
                if (!$student) {
                    $errorMessage = 'Your student account could not be verified. Please log in again.';
                } else {
                    // Database query disabled for commit
                    // $duplicate = $pdo->prepare(
                        "SELECT COUNT(*) FROM `Inquiry` WHERE listing_id = ? AND student_user_id = ?\n"
                        . "AND status IN ('pending', 'approved', 'confirmed')"
                    );
                    // Database query disabled for commit
                    // $duplicate->execute([(int) $listingId, (int) $userId]);
                    $existingInquiry = (int) $duplicate->fetchColumn() > 0;
                }
            }
        }
    } catch (Throwable $exception) {
        error_log('UniStay public inquiry: ' . get_class($exception) . ': ' . $exception->getMessage());
        http_response_code(503);
        $errorMessage = 'Inquiry details are temporarily unavailable. Please try again.';
    }
}

$status = (string) ($boardingPlace['availability'] ?? 'unavailable');
$statusText = $status === 'available' && $remaining === 0 ? 'Full' : match ($status) {
    'available' => 'Available',
    'soon' => 'Soon to be Available',
    default => 'Not Available',
};
$statusClass = $status === 'available' && $remaining === 0 ? 'unavailable' : $status;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= publicInquiryEscape($pageTitle) ?></title>
    <link rel="stylesheet" href="/src/styles/style.css">
    <style>
        .inquiry-property-image { min-height: 220px; background: #e7e7e7; display:flex; align-items:center; justify-content:center; overflow:hidden; text-align:center; color:#777; }
        .inquiry-property-image img { width: 100%; height: 250px; object-fit:cover; display:block; }
        .unistay-inquiry-info { margin: 0 0 16px; line-height: 1.7; }
        .unistay-inquiry-info strong { overflow-wrap:anywhere; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../components/header.php'; ?>
<section class="section section-light">
    <div class="container">
        <div class="listing-breadcrumb">
            <a href="/public/index.php">Home</a><span>/</span>
            <a href="/public/listings.php">Boarding Places</a><span>/</span>
            <?php if ($boardingPlace): ?>
                <a href="/public/listing.php?id=<?= (int) $listingId ?>">Boarding Details</a><span>/</span>
            <?php endif; ?>
            <span>Inquiry</span>
        </div>
        <div class="section-heading">
            <h2>SEND AN INQUIRY</h2>
            <p>Interested in this boarding place? Send an inquiry to the owner.</p>
        </div>
        <?php if ($errorMessage !== ''): ?>
            <div class="inquiry-message inquiry-error" role="alert"><?= publicInquiryEscape($errorMessage) ?></div>
            <a class="btn btn-secondary" href="/public/listings.php">Back to boarding places</a>
        <?php elseif ($boardingPlace): ?>
            <div class="inquiry-layout">
                <div class="inquiry-property-card">
                    <div class="inquiry-property-image">
                        <?php if ($photoUrl): ?>
                            <img src="<?= publicInquiryEscape($photoUrl) ?>"
                                 alt="Photo of <?= publicInquiryEscape($boardingPlace['title'] ?: 'boarding place') ?>">
                        <?php else: ?>
                            <div><h3>BOARDING PLACE</h3><p>Property photo not available</p></div>
                        <?php endif; ?>
                    </div>
                    <div class="inquiry-property-content">
                        <span class="status status-<?= publicInquiryEscape($statusClass) ?>">
                            <?= publicInquiryEscape($statusText) ?>
                        </span>
                        <h3><?= publicInquiryEscape($boardingPlace['title'] ?: ('Listing #' . $listingId)) ?></h3>
                        <p><?= publicInquiryEscape($boardingPlace['address'] ?: 'Location not specified') ?></p>
                        <p>Near <?= publicInquiryEscape($boardingPlace['university_name'] ?: 'University') ?></p>
                        <div class="inquiry-property-price">
                            Rs. <?= number_format((float) $boardingPlace['rent_amount']) ?>
                            <span>/ month</span>
                        </div>
                        <p><?= (int) $remaining ?> space(s) remaining</p>
                        <a href="/public/listing.php?id=<?= (int) $listingId ?>"
                           class="btn btn-secondary" style="width:100%;">View Property Details</a>
                    </div>
                </div>

                <div class="inquiry-form-container">
                    <h3>Your Information</h3>
                    <?php if (!$canInquire): ?>
                        <div class="inquiry-message inquiry-error">This boarding is not accepting inquiries right now.</div>
                    <?php elseif ($role !== 'student' || !$userId): ?>
                        <div class="inquiry-message inquiry-error">
                            Only a student account can send an inquiry. Please sign in as a student.
                        </div>
                        <?php if (!$userId): ?>
                            <a href="/public/login.php" class="btn btn-primary">Student Login</a>
                        <?php endif; ?>
                    <?php elseif ($existingInquiry): ?>
                        <div class="inquiry-message inquiry-success">You already have a pending or active inquiry for this listing.</div>
                        <a href="/student/inquiries.php" class="btn btn-primary">View My Inquiries</a>
                    <?php elseif ($student): ?>
                        <p class="inquiry-description">
                            Your account details will identify you to the owner. Only your message is saved in the Inquiry record.
                        </p>
                        <div class="unistay-inquiry-info">
                            <div><strong>Name:</strong> <?= publicInquiryEscape($student['full_name']) ?></div>
                            <div><strong>Email:</strong> <?= publicInquiryEscape($student['email']) ?></div>
                            <?php if (!empty($student['phone'])): ?>
                                <div><strong>Phone on profile:</strong> <?= publicInquiryEscape($student['phone']) ?></div>
                            <?php endif; ?>
                        </div>
                        <!-- Reuse the EXISTING student inquiry creation handler and CSRF validation. -->
                        <form action="/student/inquiries.php" method="POST" class="inquiry-form">
                            <input type="hidden" name="csrf_token" value="<?= publicInquiryEscape(module_csrf_token()) ?>">
                            <input type="hidden" name="action" value="create">
                            <input type="hidden" name="listing_id" value="<?= (int) $listingId ?>">
                            <div class="form-group">
                                <label for="message">Inquiry Message</label>
                                <textarea id="message" name="message" class="form-control"
                                    rows="5" minlength="5" maxlength="2000"
                                    placeholder="Hi! I'm interested in your boarding place. Could you tell me more about availability and house rules?"
                                    required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%;">Submit Inquiry</button>
                            <p class="inquiry-form-note">
                                The owner can review your inquiry. Owner approval is not a confirmed tenancy.
                            </p>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>
