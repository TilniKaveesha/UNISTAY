<?php
// frontend/src/pages/listing.php
// Public boarding details: no login required for viewing.
declare(strict_types=1);
require_once __DIR__ . '/../../../backend/includes/database.php';

function detail_escape(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Only use valid HTTPS URLs stored by the image upload module.
function detail_image_url(mixed $url): ?string
{
    $url = trim((string) ($url ?? ''));
    if (strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }
    return strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' ? $url : null;
}

$pageTitle = 'Boarding Details | UniStay';
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$boardingPlace = null;
$photos = [];
$errorMessage = '';

if ($id === false || $id === null) {
    http_response_code(404);
    $errorMessage = 'Invalid boarding place link.';
} else {
    try {
        $pdo = db();
        // Database query disabled for commit
        // $stmt = $pdo->prepare(<<<'SQL'
            SELECT b.listing_id, b.title, b.address, b.description, b.facilities,
                   b.gender_category, b.available_from, b.cover_image_url,
                   b.rent_amount, b.capacity, b.availability,
                   u.name AS university_name, u.city AS university_city,
                   owner.full_name AS owner_name,
                   (SELECT COUNT(*) FROM `Tenancy` t
                    WHERE t.listing_id = b.listing_id AND t.status = 'active') AS occupied
            FROM `BoardingListing` b
            LEFT JOIN `University` u ON u.university_id = b.university_id
            LEFT JOIN `User` owner ON owner.user_id = b.owner_id
            WHERE b.listing_id = ?
              AND b.availability IN ('available', 'soon', 'unavailable')
            LIMIT 1
            SQL);
        // Database query disabled for commit
        // $stmt->execute([(int) $id]);
        $boardingPlace = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($boardingPlace === null) {
            http_response_code(404);
            $errorMessage = 'This boarding place was not found.';
        } else {
            // Database query disabled for commit
            // $photoQuery = $pdo->prepare(<<<'SQL'
                SELECT file_path FROM `ListingPhoto`
                WHERE listing_id = ?
                ORDER BY is_cover DESC, photo_id ASC
                LIMIT 12
                SQL);
            // Database query disabled for commit
            // $photoQuery->execute([(int) $id]);
            foreach ($photoQuery->fetchAll(PDO::FETCH_COLUMN) as $url) {
                $validUrl = detail_image_url($url);
                if ($validUrl !== null && !in_array($validUrl, $photos, true)) {
                    $photos[] = $validUrl;
                }
            }
            if ($photos === []) {
                $cover = detail_image_url($boardingPlace['cover_image_url']);
                if ($cover !== null) {
                    $photos[] = $cover;
                }
            }
            $pageTitle = ($boardingPlace['title'] ?: 'Boarding Details') . ' | UniStay';
        }
    } catch (Throwable $error) {
        error_log('UniStay public boarding details: ' . $error->getMessage());
        http_response_code(503);
        $errorMessage = 'Boarding details are temporarily unavailable. Please try again later.';
    }
}

$facilities = [];
$remaining = 0;
$canInquire = false;
$status = '';
$statusText = '';
$genderText = '';

if ($boardingPlace !== null && $errorMessage === '') {
    // Owner forms currently store facilities as comma-separated text.
    $facilities = array_values(array_filter(array_map(
        'trim',
        preg_split('/[,\r\n]+/', (string) ($boardingPlace['facilities'] ?? '')) ?: []
    )));

    $status = (string) $boardingPlace['availability'];
    $remaining = max(0, (int) $boardingPlace['capacity'] - (int) $boardingPlace['occupied']);
    $canInquire = $status === 'available' && $remaining > 0;
    $statusText = $status === 'available' && $remaining === 0
        ? 'Full'
        : match ($status) {
            'available' => 'Available',
            'soon' => 'Soon to be Available',
            default => 'Not Available',
        };
    $genderText = match ((string) ($boardingPlace['gender_category'] ?? 'any')) {
        'male' => 'Male',
        'female' => 'Female',
        default => 'Male / Female',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= detail_escape($pageTitle) ?></title>
    <link rel="stylesheet" href="/src/styles/style.css">
    <style>
        .unistay-photo-gallery { margin-bottom: 35px; }
        .unistay-photo-gallery .listing-detail-image { overflow: hidden; }
        .unistay-photo-gallery .listing-detail-image img {
            display: block; width: 100%; height: min(52vw, 430px); min-height: 260px;
            object-fit: cover;
        }
        .unistay-photo-thumbnails { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
        .unistay-photo-thumbnails button {
            border: 2px solid transparent; background: none; padding: 0;
            cursor: pointer; border-radius: 8px; overflow: hidden;
        }
        .unistay-photo-thumbnails button.is-active { border-color: #2563eb; }
        .unistay-photo-thumbnails img { display: block; width: 90px; height: 65px; object-fit: cover; }
        .unistay-detail-empty { padding: 35px 0; text-align: center; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../components/header.php'; ?>

<section class="section section-light">
    <div class="container">
        <div class="listing-breadcrumb">
            <a href="/public/index.php">Home</a>
            <span>/</span>
            <a href="/public/listings.php">Boarding Places</a>
            <span>/</span>
            <span>Details</span>
        </div>

        <?php if ($errorMessage !== ''): ?>
            <div class="unistay-detail-empty">
                <h2>Boarding Place Unavailable</h2>
                <p><?= detail_escape($errorMessage) ?></p>
                <a href="/public/listings.php" class="btn btn-primary">Back to Listings</a>
            </div>
        <?php else: ?>
            <div class="section-heading">
                <h2><?= detail_escape($boardingPlace['title'] ?: ('Boarding Place #' . $id)) ?></h2>
                <p><?= detail_escape($boardingPlace['address'] ?: ($boardingPlace['university_city'] ?: 'Location not specified')) ?></p>
            </div>

            <!-- Cloudinary listing gallery (with fallback when no photos exist) -->
            <div class="unistay-photo-gallery">
                <?php if ($photos !== []): ?>
                    <div class="listing-detail-image">
                        <img id="boarding-main-photo" src="<?= detail_escape($photos[0]) ?>"
                             alt="Photo of <?= detail_escape($boardingPlace['title'] ?: 'boarding place') ?>"
                             referrerpolicy="no-referrer">
                    </div>
                    <?php if (count($photos) > 1): ?>
                        <div class="unistay-photo-thumbnails" aria-label="Property photo gallery">
                            <?php foreach ($photos as $index => $photo): ?>
                                <button type="button" class="<?= $index === 0 ? 'is-active' : '' ?>"
                                        data-photo="<?= detail_escape($photo) ?>"
                                        aria-label="Show boarding photo <?= $index + 1 ?>"
                                        aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>">
                                    <img src="<?= detail_escape($photo) ?>" alt="" loading="lazy" referrerpolicy="no-referrer">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="listing-detail-image" style="background:#e7e7e7;display:flex;align-items:center;justify-content:center;color:#777;min-height:300px;text-align:center;">
                        <div><h2>BOARDING PLACE IMAGE</h2><p>No property photos uploaded yet</p></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="listing-detail-grid">
                <div class="listing-detail-content">
                    <div class="listing-detail-section">
                        <h3>About This Boarding Place</h3>
                        <p><?= nl2br(detail_escape($boardingPlace['description'] ?: 'The owner has not added a description yet.')) ?></p>
                    </div>
                    <div class="listing-detail-section">
                        <h3>Property Information</h3>
                        <div class="detail-info-grid">
                            <div class="detail-info-item">
                                <span>University</span>
                                <strong><?= detail_escape($boardingPlace['university_name'] ?: 'Not specified') ?></strong>
                            </div>
                            <div class="detail-info-item">
                                <span>Location</span>
                                <strong><?= detail_escape($boardingPlace['address'] ?: 'Not specified') ?></strong>
                            </div>
                            <div class="detail-info-item">
                                <span>Gender Suitability</span>
                                <strong><?= detail_escape($genderText) ?></strong>
                            </div>
                            <div class="detail-info-item">
                                <span>Capacity</span>
                                <strong><?= (int) $boardingPlace['capacity'] ?> Students</strong>
                            </div>
                            <div class="detail-info-item">
                                <span>Remaining Spaces</span>
                                <strong><?= $remaining ?></strong>
                            </div>
                            <?php if ($status === 'soon' && !empty($boardingPlace['available_from'])): ?>
                                <div class="detail-info-item">
                                    <span>Expected Availability</span>
                                    <strong><?= detail_escape($boardingPlace['available_from']) ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="listing-detail-section">
                        <h3>Facilities</h3>
                        <?php if ($facilities !== []): ?>
                            <div class="facilities-grid">
                                <?php foreach ($facilities as $facility): ?>
                                    <div class="facility-item"><span>✓</span> <?= detail_escape($facility) ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>Facilities have not been listed yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- INQUIRY CARD -->
                <div class="listing-detail-sidebar">
                    <div class="detail-price-card">
                        <div class="detail-status">
                            <span class="status <?= $status === 'available' && $remaining > 0 ? 'status-available' : ($status === 'soon' ? 'status-soon' : 'status-unavailable') ?>">
                                <?= detail_escape($statusText) ?>
                            </span>
                        </div>
                        <div class="detail-rent">
                            Rs. <?= number_format((float) $boardingPlace['rent_amount']) ?>
                            <span>/ month</span>
                        </div>
                        <div class="detail-owner">
                            <h3>Owner Information</h3>
                            <p><?= detail_escape($boardingPlace['owner_name'] ?: 'Boarding Owner') ?></p>
                            <p>Contact details are shared through the inquiry process.</p>
                        </div>
                        <?php if ($canInquire): ?>
                            <a href="/public/inquiry.php?listing_id=<?= (int) $id ?>"
                               class="btn btn-primary" style="width:100%;">Send an Inquiry</a>
                        <?php else: ?>
                            <p class="detail-note">This boarding is not accepting new inquiries right now.</p>
                        <?php endif; ?>
                        <p class="detail-note">Send an inquiry to ask the owner about this property.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../components/footer.php'; ?>
<script>
    document.querySelectorAll('.unistay-photo-thumbnails [data-photo]').forEach(button => {
        button.addEventListener('click', () => {
            const mainPhoto = document.getElementById('boarding-main-photo');
            if (!mainPhoto) return;
            mainPhoto.src = button.dataset.photo;
            document.querySelectorAll('.unistay-photo-thumbnails [data-photo]').forEach(other => {
                const active = other === button;
                other.classList.toggle('is-active', active);
                other.setAttribute('aria-pressed', String(active));
            });
        });
    });
</script>
</body>
</html>
