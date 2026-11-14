<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/includes/unistay_module.php';

$ownerId = module_require('owner');
$pdo = db();
$path = '/owner/listings.php';

/** Validate optional URL for a publicly hosted image. No server-side downloads occur. */
function listing_image_url_from_form(mixed $value): ?string
{
    $url = trim((string)$value);
    if ($url === '') return null;
    if (strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) {
        throw new InvalidArgumentException('Provide a valid image URL (up to 2048 characters).');
    }
    $parts = parse_url($url);
    if (!$parts || strtolower((string)($parts['scheme'] ?? '')) !== 'https'
        || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
        throw new InvalidArgumentException('The image URL must be a public HTTPS link.');
    }
    return $url;
}

function listing_trim_field(mixed $value, string $field, int $max, bool $required = false): string
{
    $text = trim((string)$value);
    if ($required && $text === '') {
        throw new InvalidArgumentException("{$field} is required.");
    }
    // Count characters where mbstring is present; fall back to byte size.
    $length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    if ($length > $max) {
        throw new InvalidArgumentException("{$field} is too long (max {$max} characters).");
    }
    return $text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    module_check_csrf();
    try {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'create' || $action === 'update') {
            $title = listing_trim_field($_POST['title'] ?? '', 'Boarding name', 160, true);
            $address = listing_trim_field($_POST['address'] ?? '', 'Address', 300, true);
            $description = listing_trim_field($_POST['description'] ?? '', 'Description', 3000);
            $facilities = listing_trim_field($_POST['facilities'] ?? '', 'Facilities', 1000);
            $coverImageUrl = listing_image_url_from_form($_POST['cover_image_url'] ?? '');
            $universityId = module_positive_int($_POST['university_id'] ?? null, 'university ID');
            $capacity = module_positive_int($_POST['capacity'] ?? null, 'capacity');
            if ($capacity > 10000) throw new InvalidArgumentException('Capacity is too large.');
            $rent = module_money($_POST['rent_amount'] ?? '');

            $availability = (string)($_POST['availability'] ?? '');
            if (!in_array($availability, ['available', 'soon', 'unavailable'], true)) {
                throw new InvalidArgumentException('Choose a valid availability status.');
            }
            $gender = (string)($_POST['gender_category'] ?? 'any');
            if (!in_array($gender, ['any', 'male', 'female'], true)) {
                throw new InvalidArgumentException('Choose a valid accommodation category.');
            }
            $availableFrom = null;
            if ($availability === 'soon') {
                $dateValue = trim((string)($_POST['available_from'] ?? ''));
                $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateValue);
                $errors = DateTimeImmutable::getLastErrors();
                if (!$date || $date->format('Y-m-d') !== $dateValue
                    || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
                    throw new InvalidArgumentException('Choose a valid expected availability date.');
                }
                $availableFrom = $date->format('Y-m-d');
            }

            // Database query disabled for commit
            // $q = $pdo->prepare('SELECT university_id FROM `University` WHERE university_id = ? LIMIT 1');
            // Database query disabled for commit
            // $q->execute([$universityId]);
            if (!$q->fetchColumn()) {
                throw new InvalidArgumentException('Choose an existing university.');
            }
            if ($action === 'create') {
                // Database query disabled for commit
                // $id = (int)$pdo->query('SELECT NEXTVAL(unistay_listing_seq)')->fetchColumn();
                // Database query disabled for commit
                // $q = $pdo->prepare('INSERT INTO `BoardingListing`
                    (listing_id, owner_id, university_id, rent_amount, capacity, availability,
                     title, address, description, facilities, gender_category, available_from, cover_image_url)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                // Database query disabled for commit
                // $q->execute([$id, $ownerId, $universityId, $rent, $capacity, $availability,
                    $title, $address, $description !== '' ? $description : null,
                    $facilities !== '' ? $facilities : null, $gender, $availableFrom, $coverImageUrl]);
                // The listing must exist before photos can be attached to its ID.
                // Take owners directly to the Cloudinary upload form after creation.
                module_flash('Listing created! Add property photos here, or return to your listings.');
                module_redirect('/owner/photos.php?id=' . $id);
            } else {
                $id = module_positive_int($_POST['listing_id'] ?? null, 'listing ID');
                $pdo->beginTransaction();
                // Database query disabled for commit
                // $lock = $pdo->prepare('SELECT listing_id FROM `BoardingListing` WHERE listing_id = ? AND owner_id = ? FOR UPDATE');
                // Database query disabled for commit
                // $lock->execute([$id, $ownerId]);
                if (!$lock->fetchColumn()) throw new InvalidArgumentException('Listing not found or not yours.');
                // Database query disabled for commit
                // $count = $pdo->prepare("SELECT COUNT(*) FROM `Tenancy` WHERE listing_id = ? AND status = 'active'");
                // Database query disabled for commit
                // $count->execute([$id]);
                if ((int)$count->fetchColumn() > $capacity) {
                    throw new InvalidArgumentException('Capacity cannot be less than current active occupancy.');
                }
                // Database query disabled for commit
                // $q = $pdo->prepare('UPDATE `BoardingListing` SET
                    university_id = ?, rent_amount = ?, capacity = ?, availability = ?,
                    title = ?, address = ?, description = ?, facilities = ?, gender_category = ?,
                    available_from = ?, cover_image_url = ?
                    WHERE listing_id = ? AND owner_id = ?');
                // Database query disabled for commit
                // $q->execute([$universityId, $rent, $capacity, $availability,
                    $title, $address, $description !== '' ? $description : null,
                    $facilities !== '' ? $facilities : null, $gender, $availableFrom, $coverImageUrl,
                    $id, $ownerId]);
                $pdo->commit();
                module_flash('Boarding listing updated.');
            }
        } elseif ($action === 'deactivate') {
            $id = module_positive_int($_POST['listing_id'] ?? null, 'listing ID');
            // Database query disabled for commit
            // $q = $pdo->prepare("UPDATE `BoardingListing` SET availability = 'unavailable', available_from = NULL WHERE listing_id = ? AND owner_id = ?");
            // Database query disabled for commit
            // $q->execute([$id, $ownerId]);
            module_flash($q->rowCount() ? 'Listing marked unavailable; inquiry and tenancy history are preserved.' : 'Listing not found.', $q->rowCount() === 0);
        } else {
            throw new InvalidArgumentException('Unknown operation.');
        }
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        module_flash($e->getMessage(), true);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        module_fail($e, $path);
    }
    module_redirect($path);
}

// Database query disabled for commit
// $q = $pdo->prepare('SELECT b.listing_id, b.university_id, b.rent_amount, b.capacity, b.availability,
    b.title, b.address, b.description, b.facilities, b.gender_category, b.available_from, b.cover_image_url,
    u.name AS university_name, u.city AS university_city,
    (SELECT p.file_path FROM `ListingPhoto` p WHERE p.listing_id = b.listing_id ORDER BY p.is_cover DESC, p.photo_id ASC LIMIT 1) AS gallery_cover,
    (SELECT COUNT(*) FROM `Tenancy` t WHERE t.listing_id = b.listing_id AND t.status = \'active\') AS occupied
    FROM `BoardingListing` b
    LEFT JOIN `University` u ON u.university_id = b.university_id
    WHERE b.owner_id = ? ORDER BY b.listing_id DESC');
// Database query disabled for commit
// $q->execute([$ownerId]);
$listings = $q->fetchAll();
// Database query disabled for commit
// $universities = $pdo->query('SELECT university_id, name, city FROM `University` ORDER BY name, city, university_id')->fetchAll(PDO::FETCH_ASSOC);
$edit = null;
$requested = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
if ($requested) {
    foreach ($listings as $row) {
        if ((int)$row['listing_id'] === (int)$requested) { $edit = $row; break; }
    }
}
module_header('Manage Boarding Listings', 'owner');
?>
<p class="muted">Publish accommodation details students can actually compare. Your existing tenancy and inquiry records remain linked to the same listing IDs.</p>
<div class="notice success" role="note">
    <strong>Property photos:</strong>
    <?php if ($edit): ?>
        Your listing is saved. <a href="/owner/photos.php?id=<?= (int)$edit['listing_id'] ?>">Upload photos for this listing</a> (up to 10 photos).
    <?php else: ?>
        First create your listing below. You'll automatically open the cloud photo uploader after saving, where you can select image files directly from your computer.
    <?php endif; ?>
</div>
<?php if (!$universities): ?>
    <div class="notice error">No universities are registered yet. Your database team must add a row to <code>University</code> first.</div>
<?php endif; ?>
<section class="panel">
    <h2><?= $edit ? 'Edit ' . module_escape((string)($edit['title'] ?: ('Listing #' . $edit['listing_id']))) : 'Create a boarding listing' ?></h2>
    <form method="post" action="/owner/listings.php">
        <input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>">
        <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
        <?php if ($edit): ?><input type="hidden" name="listing_id" value="<?= (int)$edit['listing_id'] ?>"><?php endif; ?>
        <div class="grid">
            <div><label for="title">Boarding name *</label><input id="title" name="title" maxlength="160" required placeholder="e.g., Campus View Boarding" value="<?= module_escape((string)($edit['title'] ?? '')) ?>"></div>
            <div><label for="university_id">Nearest university *</label>
                <select id="university_id" name="university_id" required <?= !$universities ? 'disabled' : '' ?>>
                    <option value="">Choose university</option>
                    <?php foreach ($universities as $uni): ?>
                        <option value="<?= (int)$uni['university_id'] ?>" <?= $edit && (int)$edit['university_id'] === (int)$uni['university_id'] ? 'selected' : '' ?>><?= module_escape((string)($uni['name'] ?: ('University #' . $uni['university_id']))) ?><?= $uni['city'] ? ' · ' . module_escape((string)$uni['city']) : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="full-width"><label for="address">Boarding address *</label><input id="address" name="address" maxlength="300" required placeholder="No. 12, Station Road, Moratuwa" value="<?= module_escape((string)($edit['address'] ?? '')) ?>"></div>
            <div><label for="rent_amount">Monthly rent (LKR) *</label><input id="rent_amount" type="number" min="0.01" max="99999999" step="0.01" name="rent_amount" required value="<?= module_escape((string)($edit['rent_amount'] ?? '')) ?>"></div>
            <div><label for="capacity">Total available places (capacity) *</label><input id="capacity" type="number" min="1" max="10000" name="capacity" required value="<?= module_escape((string)($edit['capacity'] ?? 1)) ?>"></div>
            <div><label for="gender_category">Accommodation category</label><select id="gender_category" name="gender_category">
                <?php foreach (['any' => 'Any student', 'male' => 'Male students', 'female' => 'Female students'] as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($edit['gender_category'] ?? 'any') === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select></div>
            <div><label for="availability">Availability *</label><select id="availability" name="availability">
                <?php foreach (['available' => 'Available now', 'soon' => 'Available soon', 'unavailable' => 'Unavailable'] as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($edit['availability'] ?? 'available') === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select></div>
            <div><label for="available_from">Available from (if coming soon)</label><input id="available_from" type="date" name="available_from" value="<?= module_escape((string)($edit['available_from'] ?? '')) ?>"><small class="muted">Required when availability is “Available soon”.</small></div>
            <div class="full-width"><label for="description">Description</label><textarea id="description" name="description" maxlength="3000" placeholder="Describe the rooms, house rules and nearby transport..."><?= module_escape((string)($edit['description'] ?? '')) ?></textarea></div>
            <div class="full-width"><label for="facilities">Facilities</label><textarea id="facilities" name="facilities" maxlength="1000" placeholder="Wi-Fi, study desk, laundry, attached bathroom..."><?= module_escape((string)($edit['facilities'] ?? '')) ?></textarea><small class="muted">Separate facilities with commas.</small></div>
            <div class="full-width"><label for="cover_image_url">Alternative cover photo URL (optional)</label><input id="cover_image_url" type="url" name="cover_image_url" maxlength="2048" placeholder="https://example.com/boarding-photo.jpg" value="<?= module_escape((string)($edit['cover_image_url'] ?? '')) ?>"><small class="muted">You don't need a URL to upload photos. Save the listing, then choose image files in the Cloud Photo Uploader. This URL is only for an already-hosted image.</small></div>
        </div>
        <div class="actions" style="margin-top:16px">
            <button type="submit" <?= !$universities ? 'disabled' : '' ?>><?= $edit ? 'Save changes' : 'Create listing &amp; add photos' ?></button>
            <?php if ($edit): ?>
                <a class="btn" href="/owner/photos.php?id=<?= (int)$edit['listing_id'] ?>">📷 Upload photos</a>
                <a class="btn secondary" href="/owner/listings.php">Cancel editing</a>
            <?php endif; ?>
        </div>
    </form>
</section>
<section class="panel">
    <h2>Your listings</h2>
    <div class="cards listing-cards">
        <?php foreach ($listings as $row): ?>
            <article class="card listing-card">
                <?php if ($row['gallery_cover'] ?: $row['cover_image_url']): ?><img class="listing-cover" src="<?= module_escape((string)($row['gallery_cover'] ?: $row['cover_image_url'])) ?>" alt="Photo of <?= module_escape((string)($row['title'] ?: ('listing #' . $row['listing_id']))) ?>" loading="lazy" referrerpolicy="no-referrer"><?php else: ?><div class="listing-cover listing-placeholder">UniStay</div><?php endif; ?>
                <div class="listing-card-content">
                    <span class="tag">#<?= (int)$row['listing_id'] ?> · <?= module_escape((string)$row['availability']) ?></span>
                    <h3><?= module_escape((string)($row['title'] ?: ('Listing #' . $row['listing_id']))) ?></h3>
                    <p class="muted small"><?= module_escape((string)($row['address'] ?: 'Address not supplied yet')) ?></p>
                    <p class="muted small"><?= module_escape((string)($row['university_name'] ?: ('University #' . $row['university_id']))) ?> · <?= module_escape((string)($row['gender_category'] ?? 'any')) ?></p>
                    <p><strong>LKR <?= module_escape((string)$row['rent_amount']) ?></strong> / month</p>
                    <p class="muted small">Occupied: <?= (int)$row['occupied'] ?> / <?= (int)$row['capacity'] ?></p>
                    <div class="actions"><a class="btn" href="/owner/photos.php?id=<?= (int)$row['listing_id'] ?>">📷 Upload photos</a><a class="btn secondary" href="/owner/listings.php?edit=<?= (int)$row['listing_id'] ?>">Edit details</a>
                        <?php if ($row['availability'] !== 'unavailable'): ?>
                            <form method="post" action="/owner/listings.php" class="inline" onsubmit="return confirm('Mark this listing unavailable?');">
                                <input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>">
                                <input type="hidden" name="action" value="deactivate">
                                <input type="hidden" name="listing_id" value="<?= (int)$row['listing_id'] ?>">
                                <button class="danger" type="submit">Deactivate</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$listings): ?><p class="muted">No listings yet. Use the form above to add your first boarding.</p><?php endif; ?>
    </div>
</section>
<?php module_footer(); ?>
