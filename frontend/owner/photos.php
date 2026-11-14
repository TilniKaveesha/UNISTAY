<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/includes/unistay_module.php';
require_once __DIR__ . '/../../backend/includes/cloudinary.php';

$ownerId = module_require('owner');
$pdo = db();
$listingId = filter_var($_GET['id'] ?? $_POST['listing_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if (!$listingId) {
    http_response_code(404);
    exit('Boarding listing not found.');
}
$listingId = (int)$listingId;
$page = '/owner/photos.php?id=' . $listingId;

// Legacy local images are kept readable; removing them from the gallery also removes
// the local file on this server. New uploads NEVER persist to local uploads/listings.
function unistay_delete_legacy_local_photo(string $url, int $listingId): void
{
    $pattern = '~^/uploads/listings/' . preg_quote((string)$listingId, '~') . '/[a-f0-9]{32}\.(?:jpg|png|webp)$~D';
    if (!preg_match($pattern, $url)) {
        return;
    }
    $path = __DIR__ . '/../uploads/listings/' . $listingId . '/' . basename($url);
    if (is_file($path) && !@unlink($path)) {
        error_log('UniStay could not remove old local image: ' . $path);
    }
}

function unistay_rollback_cloud_uploads(array $uploaded, int $listingId): void
{
    foreach ($uploaded as $record) {
        try {
            unistay_cloudinary_delete((string)$record['cloud_public_id'], $listingId);
        } catch (Throwable $cleanupError) {
            error_log('UniStay orphaned Cloudinary image after upload rollback: '
                . $record['cloud_public_id'] . ': ' . $cleanupError->getMessage());
        }
    }
}

// Every action verifies the authenticated user owns the listing.
// Database query disabled for commit
// $check = $pdo->prepare('SELECT listing_id, title FROM `BoardingListing` WHERE listing_id = ? AND owner_id = ? LIMIT 1');
// Database query disabled for commit
// $check->execute([$listingId, $ownerId]);
$listing = $check->fetch();
if (!$listing) {
    http_response_code(404);
    exit('Boarding listing not found, or you are not its owner.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    module_check_csrf();
    $uploaded = [];
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'upload') {
            $uploads = $_FILES['photos'] ?? null;
            if (!is_array($uploads) || !isset($uploads['error']) || !is_array($uploads['error'])) {
                throw new InvalidArgumentException('Choose at least one photo.');
            }
            $fileCount = count($uploads['error']);
            if ($fileCount < 1 || $fileCount > 5) {
                throw new InvalidArgumentException('Choose 1–5 photos at a time.');
            }
            // Fast capacity check to avoid unnecessary network calls.
            // Database query disabled for commit
            // $countStmt = $pdo->prepare('SELECT COUNT(*) FROM `ListingPhoto` WHERE listing_id = ?');
            // Database query disabled for commit
            // $countStmt->execute([$listingId]);
            if ((int)$countStmt->fetchColumn() + $fileCount > 10) {
                throw new InvalidArgumentException('Maximum 10 photos per listing.');
            }
            $files = [];
            for ($i = 0; $i < $fileCount; ++$i) {
                $files[] = [
                    'error' => $uploads['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'tmp_name' => $uploads['tmp_name'][$i] ?? '',
                    'size' => $uploads['size'][$i] ?? 0,
                ];
            }

            // Upload to Cloudinary BEFORE starting the TiDB transaction; we do not
            // hold a database lock while waiting for an external network service.
            foreach ($files as $file) {
                $uploaded[] = unistay_cloudinary_upload($file, $listingId);
            }

            // Prevent concurrent requests from exceeding 10 images per listing.
            $pdo->beginTransaction();
            // Database query disabled for commit
            // $lock = $pdo->prepare('SELECT listing_id FROM `BoardingListing` WHERE listing_id = ? AND owner_id = ? FOR UPDATE');
            // Database query disabled for commit
            // $lock->execute([$listingId, $ownerId]);
            if (!$lock->fetchColumn()) {
                throw new InvalidArgumentException('You cannot edit this listing.');
            }
            // Database query disabled for commit
            // $countStmt->execute([$listingId]);
            $existingCount = (int)$countStmt->fetchColumn();
            if ($existingCount + count($uploaded) > 10) {
                throw new InvalidArgumentException('Photo limit reached while uploading. Please refresh.');
            }
            // Database query disabled for commit
            // $insert = $pdo->prepare('INSERT INTO `ListingPhoto` (listing_id, file_path, cloud_public_id, is_cover) VALUES (?, ?, ?, ?)');
            foreach ($uploaded as $index => $photo) {
                // Database query disabled for commit
                // $insert->execute([
                    $listingId,
                    $photo['file_path'],
                    $photo['cloud_public_id'],
                    ($existingCount === 0 && $index === 0) ? 1 : 0,
                ]);
            }
            $pdo->commit();
            $uploaded = []; // These images are now owned by database records.
            module_flash($fileCount . ' photo(s) uploaded to Cloudinary.');

        } elseif ($action === 'cover' || $action === 'remove') {
            $photoId = module_positive_int($_POST['photo_id'] ?? null, 'photo ID');
            $pdo->beginTransaction();
            // Database query disabled for commit
            // $lock = $pdo->prepare('SELECT listing_id FROM `BoardingListing` WHERE listing_id = ? AND owner_id = ? FOR UPDATE');
            // Database query disabled for commit
            // $lock->execute([$listingId, $ownerId]);
            if (!$lock->fetchColumn()) {
                throw new InvalidArgumentException('You cannot edit this listing.');
            }
            // Database query disabled for commit
            // $get = $pdo->prepare('SELECT photo_id, file_path, cloud_public_id, is_cover FROM `ListingPhoto` WHERE photo_id = ? AND listing_id = ? FOR UPDATE');
            // Database query disabled for commit
            // $get->execute([$photoId, $listingId]);
            $photo = $get->fetch();
            if (!$photo) {
                throw new InvalidArgumentException('Photo does not belong to this listing.');
            }

            if ($action === 'cover') {
                // Database query disabled for commit
                // $pdo->prepare('UPDATE `ListingPhoto` SET is_cover = 0 WHERE listing_id = ?')->execute([$listingId]);
                // Database query disabled for commit
                // $pdo->prepare('UPDATE `ListingPhoto` SET is_cover = 1 WHERE listing_id = ? AND photo_id = ?')
                    // Database query disabled for commit
                    // ->execute([$listingId, $photoId]);
                $pdo->commit();
                module_flash('Cover photo updated.');
            } else {
                // Database query disabled for commit
                // $pdo->prepare('DELETE FROM `ListingPhoto` WHERE photo_id = ? AND listing_id = ?')->execute([$photoId, $listingId]);
                // When the cover is removed, promote the first remaining photo.
                if ((int)$photo['is_cover'] === 1) {
                    // Database query disabled for commit
                    // $next = $pdo->prepare('SELECT photo_id FROM `ListingPhoto` WHERE listing_id = ? ORDER BY photo_id ASC LIMIT 1');
                    // Database query disabled for commit
                    // $next->execute([$listingId]);
                    $nextId = $next->fetchColumn();
                    if ($nextId !== false) {
                        // Database query disabled for commit
                        // $pdo->prepare('UPDATE `ListingPhoto` SET is_cover = 1 WHERE photo_id = ? AND listing_id = ?')
                            // Database query disabled for commit
                            // ->execute([(int)$nextId, $listingId]);
                    }
                }
                $pdo->commit();

                // Database deletion is final. A cloud deletion failure is logged so
                // an orphaned asset can be cleaned up later without breaking the UI.
                if (!empty($photo['cloud_public_id'])) {
                    try {
                        unistay_cloudinary_delete((string)$photo['cloud_public_id'], $listingId);
                        module_flash('Photo removed from the gallery and cloud storage.');
                    } catch (Throwable $cleanupError) {
                        error_log('UniStay Cloudinary cleanup required for ' . $photo['cloud_public_id']
                            . ': ' . $cleanupError->getMessage());
                        module_flash('Photo removed from the gallery; cloud cleanup could not be confirmed. Check server logs.', true);
                    }
                } else {
                    unistay_delete_legacy_local_photo((string)$photo['file_path'], $listingId);
                    module_flash('Photo removed from the gallery.');
                }
            }
        } else {
            throw new InvalidArgumentException('Unknown photo action.');
        }
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        unistay_rollback_cloud_uploads($uploaded, $listingId);
        module_flash($e->getMessage(), true);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        unistay_rollback_cloud_uploads($uploaded, $listingId);
        module_fail($e, $page);
    }
    module_redirect($page);
}

// Database query disabled for commit
// $getPhotos = $pdo->prepare('SELECT photo_id, file_path, cloud_public_id, is_cover FROM `ListingPhoto` WHERE listing_id = ? ORDER BY is_cover DESC, photo_id ASC');
// Database query disabled for commit
// $getPhotos->execute([$listingId]);
$photos = $getPhotos->fetchAll();
module_header('Photos · ' . (string)($listing['title'] ?: ('Listing #' . $listingId)), 'owner');
?>
<p class="muted">New photos are stored in Cloudinary and shared by all UniStay users. Only the listing owner can upload, remove, or change the cover.</p>
<a class="back-link" href="/owner/listings.php">← Back to listings</a>
<section class="panel">
  <h2>Upload photos <span class="muted small">(<?= count($photos) ?>/10)</span></h2>
  <form action="<?= module_escape($page) ?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>">
    <input type="hidden" name="listing_id" value="<?= $listingId ?>">
    <input type="hidden" name="action" value="upload">
    <label for="photos">Choose up to five images</label>
    <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required>
    <p class="muted small">JPEG, PNG or WebP · 4 MB maximum per photo · 10 photos per listing. Avoid uploading photos containing private location metadata.</p>
    <button type="submit" <?= count($photos) >= 10 ? 'disabled' : '' ?>>Upload to cloud</button>
  </form>
</section>
<section class="panel">
  <h2>Photo gallery</h2>
  <div class="photo-manager-grid">
    <?php foreach ($photos as $photo): ?>
      <article class="photo-manager-card">
        <img src="<?= module_escape((string)$photo['file_path']) ?>" alt="Boarding property photo" loading="lazy" referrerpolicy="no-referrer">
        <div class="photo-manager-actions">
          <?php if ((int)$photo['is_cover'] === 1): ?><span class="tag">Cover</span><?php endif; ?>
          <?php if (!empty($photo['cloud_public_id'])): ?><span class="tag">Cloud</span><?php endif; ?>
          <form class="inline" method="post" action="<?= module_escape($page) ?>">
            <input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>">
            <input type="hidden" name="listing_id" value="<?= $listingId ?>">
            <input type="hidden" name="photo_id" value="<?= (int)$photo['photo_id'] ?>">
            <input type="hidden" name="action" value="cover">
            <button type="submit" class="secondary">Set cover</button>
          </form>
          <form class="inline" method="post" action="<?= module_escape($page) ?>" onsubmit="return confirm('Remove this photo?');">
            <input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>">
            <input type="hidden" name="listing_id" value="<?= $listingId ?>">
            <input type="hidden" name="photo_id" value="<?= (int)$photo['photo_id'] ?>">
            <input type="hidden" name="action" value="remove">
            <button type="submit" class="danger">Remove</button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
    <?php if (!$photos): ?><p class="muted">No photos yet. Upload some pictures of this property.</p><?php endif; ?>
  </div>
</section>
<?php module_footer(); ?>
