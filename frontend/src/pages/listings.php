
<?php
// frontend/src/pages/listings.php
// Public visitors can browse listings without logging in.
require_once __DIR__ . '/../../../backend/includes/database.php';

$pageTitle = 'Explore Boarding Places | UniStay';

// Safe HTML output for data coming from the database or URL.
$e = static fn ($value): string => htmlspecialchars(
    (string) ($value ?? ''),
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);

// Keep the same URL parameter names used by the existing homepage form.
$getParam = static function (string $key): string {
    $value = $_GET[$key] ?? '';
    return is_string($value) ? trim($value) : '';
};

$selectedUniversity = $getParam('university');
if ($selectedUniversity === '') {
    $selectedUniversity = $getParam('university_id');
}
$selectedGender = $getParam('gender');
$selectedAvailability = $getParam('availability');

if (!in_array($selectedGender, ['', 'male', 'female'], true)) {
    $selectedGender = '';
}
if (!in_array($selectedAvailability, ['', 'available', 'soon', 'unavailable'], true)) {
    $selectedAvailability = '';
}

$universities = [];
$boardingPlaces = [];
$loadError = false;

try {
    $pdo = db(); // Existing TiDB connection. No new configuration needed.

    // Database query disabled for commit
    // $universities = $pdo->query(
        'SELECT university_id, name, city FROM `University` ORDER BY name'
    )->fetchAll(PDO::FETCH_ASSOC);

    $sql = <<<'SQL'
        SELECT
            b.listing_id,
            b.title,
            b.address,
            b.rent_amount,
            b.capacity,
            b.availability,
            b.gender_category,
            b.cover_image_url,
            u.name AS university_name,
            u.city AS university_city,
            (
                SELECT p.file_path
                FROM `ListingPhoto` p
                WHERE p.listing_id = b.listing_id
                ORDER BY p.is_cover DESC, p.photo_id ASC
                LIMIT 1
            ) AS gallery_cover,
            (
                SELECT COUNT(*)
                FROM `Tenancy` t
                WHERE t.listing_id = b.listing_id AND t.status = 'active'
            ) AS occupied
        FROM `BoardingListing` b
        LEFT JOIN `University` u ON u.university_id = b.university_id
        WHERE b.availability IN ('available', 'soon', 'unavailable')
        SQL;

    $params = [];

    // University can arrive as an ID from the dropdown, or as a name from an older form.
    if ($selectedUniversity !== '') {
        if (ctype_digit($selectedUniversity) && (int) $selectedUniversity > 0) {
            $sql .= ' AND b.university_id = :university_id';
            $params[':university_id'] = (int) $selectedUniversity;
        } elseif (strlen($selectedUniversity) <= 150) {
            $sql .= ' AND u.name = :university_name';
            $params[':university_name'] = $selectedUniversity;
        }
    }

    if ($selectedGender !== '') {
        $sql .= " AND b.gender_category IN ('any', 'both', :gender)";
        $params[':gender'] = $selectedGender;
    }

    if ($selectedAvailability !== '') {
        $sql .= ' AND b.availability = :availability';
        $params[':availability'] = $selectedAvailability;
    }

    $sql .= <<<SQL

        ORDER BY
            CASE b.availability
                WHEN 'available' THEN 0
                WHEN 'soon' THEN 1
                ELSE 2
            END,
            b.listing_id DESC
        LIMIT 60
        SQL;

    // Database query disabled for commit
    // $stmt = $pdo->prepare($sql);
    // Database query disabled for commit
    // $stmt->execute($params);
    $boardingPlaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
    // Don't expose DB credentials or queries to visitors.
    error_log('UniStay public listings: ' . $exception->getMessage());
    $loadError = true;
    http_response_code(503);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($pageTitle) ?></title>
    <link rel="stylesheet" href="/src/styles/style.css">
</head>
<body>

<?php include __DIR__ . '/../components/header.php'; ?>

<!-- PAGE INTRODUCTION -->
<section class="section section-light">
    <div class="container">
        <div class="section-heading">
            <h2>FIND YOUR IDEAL BOARDING PLACE</h2>
            <p>Explore accommodation options near your university and find a place that suits your needs.</p>
        </div>

        <!-- SEARCH AND FILTER FORM -->
        <div class="search-panel">
            <form action="/public/listings.php" method="GET" class="search-form">
                <div class="form-group">
                    <label for="university">University</label>
                    <select name="university" id="university" class="form-control">
                        <option value="">All Universities</option>
                        <?php foreach ($universities as $university): ?>
                            <option value="<?= (int) $university['university_id'] ?>"
                                <?= $selectedUniversity === (string) $university['university_id'] ||
                                    $selectedUniversity === (string) $university['name'] ? 'selected' : '' ?>>
                                <?= $e($university['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="gender">Gender Preference</label>
                    <select name="gender" id="gender" class="form-control">
                        <option value="">All Categories</option>
                        <option value="male" <?= $selectedGender === 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= $selectedGender === 'female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="availability">Availability</label>
                    <select name="availability" id="availability" class="form-control">
                        <option value="">All Availability</option>
                        <option value="available" <?= $selectedAvailability === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="soon" <?= $selectedAvailability === 'soon' ? 'selected' : '' ?>>Soon to be Available</option>
                        <option value="unavailable" <?= $selectedAvailability === 'unavailable' ? 'selected' : '' ?>>Not Available</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Search Places</button>
            </form>
        </div>
    </div>
</section>

<!-- SEARCH RESULTS -->
<section class="section">
    <div class="container">
        <div class="section-heading">
            <h2>BOARDING PLACES</h2>
            <?php if (!$loadError): ?>
                <p><?= count($boardingPlaces) ?> boarding place(s) found<?= count($boardingPlaces) === 60 ? ' (showing up to 60)' : '' ?>.</p>
            <?php endif; ?>
        </div>

        <?php if ($loadError): ?>
            <div class="section-heading">
                <h3>Could not load boarding places</h3>
                <p>Please check the TiDB connection and try again.</p>
            </div>
        <?php elseif (count($boardingPlaces) > 0): ?>
            <div class="listing-grid">
                <?php foreach ($boardingPlaces as $place): ?>
                    <?php
                    $title = trim((string) ($place['title'] ?? ''));
                    if ($title === '') {
                        $title = 'Boarding Place #' . $place['listing_id'];
                    }
                    $location = (string) ($place['address'] ?: $place['university_city'] ?: 'Location not specified');
                    $universityName = (string) ($place['university_name'] ?: 'University not specified');
                    $gender = (string) ($place['gender_category'] ?? 'any');
                    $remaining = max(0, (int) $place['capacity'] - (int) $place['occupied']);
                    $status = (string) $place['availability'];
                    $isFull = $status === 'available' && $remaining === 0;
                    $statusClass = $isFull ? 'status-unavailable' : 'status-' . $status;
                    $statusText = $isFull ? 'Full' : match ($status) {
                        'available' => 'Available',
                        'soon' => 'Soon to be Available',
                        default => 'Not Available',
                    };
                    $image = trim((string) ($place['gallery_cover'] ?: $place['cover_image_url'] ?: ''));
                    $safeImage = filter_var($image, FILTER_VALIDATE_URL)
                        && strtolower((string) parse_url($image, PHP_URL_SCHEME)) === 'https';
                    ?>
                    <div class="listing-card">
                        <div class="listing-image" style="background:#e7e7e7; min-height:210px; display:flex; align-items:center; justify-content:center; color:#777; overflow:hidden;">
                            <?php if ($safeImage): ?>
                                <img src="<?= $e($image) ?>" alt="Photo of <?= $e($title) ?>"
                                     loading="lazy" style="width:100%; height:220px; object-fit:cover; display:block;">
                            <?php else: ?>
                                Boarding Image
                            <?php endif; ?>
                        </div>
                        <div class="listing-content">
                            <span class="status <?= $e($statusClass) ?>"><?= $e($statusText) ?></span>
                            <h3><?= $e($title) ?></h3>
                            <p class="listing-location">
                                <?= $e($location) ?><br>
                                Near <?= $e($universityName) ?>
                            </p>
                            <div class="listing-price">
                                Rs. <?= number_format((float) $place['rent_amount']) ?>
                                <span>/ month</span>
                            </div>
                            <div class="listing-meta">
                                <span class="status status-available">
                                    <?= $e($gender === 'any' || $gender === 'both' ? 'Male / Female' : ucfirst($gender)) ?>
                                </span>
                                <?php if ($status === 'available' && !$isFull): ?>
                                    <span><?= $remaining ?> space(s) left</span>
                                <?php endif; ?>
                            </div>
                            <a href="/public/listing.php?id=<?= (int) $place['listing_id'] ?>"
                               class="btn btn-primary" style="width:100%;">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="section-heading">
                <h3>No Matching Boarding Places Found</h3>
                <p>Try changing your university, gender or availability filters.</p>
                <a href="/public/listings.php" class="btn btn-primary">Clear Filters</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>
