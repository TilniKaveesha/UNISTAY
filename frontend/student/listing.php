<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/includes/unistay_module.php';
$studentId = module_require('student');
$pdo = db();
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(404); exit('Boarding listing not found.'); }
$q = $pdo->prepare('SELECT b.listing_id, b.title, b.address, b.description, b.facilities, b.gender_category,
    b.available_from, b.cover_image_url, b.rent_amount, b.capacity, b.availability,
    u.name AS university_name, u.city AS university_city,
    owner.full_name AS owner_name, owner.phone AS owner_phone, owner.share_phone_with_inquirers,
    (SELECT COUNT(*) FROM `Inquiry` i WHERE i.listing_id = b.listing_id AND i.student_user_id = ? AND i.status IN (\'pending\', \'approved\', \'confirmed\')) AS eligible_inquiries,
    (SELECT COUNT(*) FROM `Tenancy` t WHERE t.listing_id = b.listing_id AND t.status = \'active\') AS occupied
    FROM `BoardingListing` b LEFT JOIN `University` u ON u.university_id = b.university_id
    LEFT JOIN `User` owner ON owner.user_id = b.owner_id
    WHERE b.listing_id = ? LIMIT 1');
$q->execute([$studentId, (int)$id]);
$item = $q->fetch();
if (!$item) { http_response_code(404); exit('Boarding listing not found.'); }
$photosQuery = $pdo->prepare('SELECT file_path FROM `ListingPhoto` WHERE listing_id = ? ORDER BY is_cover DESC, photo_id ASC');
$photosQuery->execute([(int)$id]);
$photoPaths = array_map(static fn(array $p): string => (string)$p['file_path'], $photosQuery->fetchAll());
// Keep previous cover URL for listings that haven't uploaded gallery photos.
if (!$photoPaths && $item['cover_image_url']) $photoPaths[] = (string)$item['cover_image_url'];

$remaining = max(0, (int)$item['capacity'] - (int)$item['occupied']);
$canInquire = $item['availability'] === 'available' && $remaining > 0;
$facilities = array_values(array_filter(array_map('trim', explode(',', (string)($item['facilities'] ?? '')))));
module_header('Boarding Details', 'student');
?>
<a class="back-link" href="/student/listings.php">← Back to search results</a>
<article class="panel listing-details">
    <?php if ($photoPaths): ?>
      <img id="photo-hero" class="listing-hero" src="<?= module_escape($photoPaths[0]) ?>" alt="Photo of this boarding place" referrerpolicy="no-referrer">
      <?php if (count($photoPaths) > 1): ?>
        <div class="listing-thumbnails" aria-label="Boarding photo gallery">
          <?php foreach ($photoPaths as $idx => $imagePath): ?>
            <button type="button" class="listing-thumbnail <?= $idx === 0 ? 'selected' : '' ?>" data-photo-url="<?= module_escape($imagePath) ?>" aria-label="View photo <?= $idx + 1 ?>">
              <img src="<?= module_escape($imagePath) ?>" alt="" loading="lazy" referrerpolicy="no-referrer">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php else: ?><div class="listing-hero listing-placeholder">UniStay</div><?php endif; ?>
    <div class="listing-details-content">
      <span class="tag"><?= module_escape((string)$item['availability']) ?></span>
      <h2><?= module_escape((string)($item['title'] ?: ('Boarding #' . $item['listing_id']))) ?></h2>
      <p class="muted"><?= module_escape((string)($item['address'] ?: 'Address not provided')) ?></p>
      <p class="muted"><?= module_escape((string)($item['university_name'] ?: 'University not specified')) ?><?= $item['university_city'] ? ' · ' . module_escape((string)$item['university_city']) : '' ?></p>
      <div class="listing-facts">
        <div><small class="muted">Monthly rent</small><strong>LKR <?= module_escape((string)$item['rent_amount']) ?></strong></div>
        <div><small class="muted">Places remaining</small><strong><?= $remaining ?> / <?= (int)$item['capacity'] ?></strong></div>
        <div><small class="muted">Category</small><strong><?= module_escape((string)($item['gender_category'] === 'any' ? 'All students' : ucfirst((string)$item['gender_category']) . ' students')) ?></strong></div>
      </div>
      <?php if ($item['availability'] === 'soon' && $item['available_from']): ?><p><strong>Expected availability:</strong> <?= module_escape((string)$item['available_from']) ?></p><?php endif; ?>
      <h3>About this place</h3>
      <p><?= nl2br(module_escape((string)($item['description'] ?: 'The owner has not added a description yet.'))) ?></p>
      <h3>Facilities</h3>
      <?php if ($facilities): ?><div class="facility-list"><?php foreach ($facilities as $feature): ?><span class="facility-chip"><?= module_escape($feature) ?></span><?php endforeach; ?></div>
      <?php else: ?><p class="muted">Facilities have not been listed yet.</p><?php endif; ?>
      <section class="owner-contact-card" aria-label="Owner contact information">
        <h3>Contact the property owner</h3>
        <?php if ((int)$item['share_phone_with_inquirers'] === 1 && (int)$item['eligible_inquiries'] > 0 && !empty($item['owner_phone'])): ?>
          <p>Owner: <strong><?= module_escape((string)($item['owner_name'] ?: 'Boarding owner')) ?></strong></p>
          <p>Phone: <a href="tel:<?= module_escape(preg_replace('/[^0-9+]/', '', (string)$item['owner_phone'])) ?>"><?= module_escape((string)$item['owner_phone']) ?></a></p>
          <p class="muted small">The owner has chosen to share this number with students who have sent an inquiry.</p>
        <?php else: ?>
          <p class="muted">Use the inquiry form to contact this owner. If they enable phone sharing, their number will appear here after you inquire.</p>
        <?php endif; ?>
      </section>
      <div class="actions" style="margin-top:22px">
          <?php if ($canInquire): ?><a class="btn" href="/student/inquiries.php?listing_id=<?= (int)$item['listing_id'] ?>">Send an inquiry</a>
          <?php else: ?><span class="tag">Not accepting inquiries right now</span><?php endif; ?>
          <a class="btn secondary" href="/student/listings.php">Browse other places</a>
      </div>
    </div>
</article>
<script>
document.querySelectorAll('[data-photo-url]').forEach(button => {
    button.addEventListener('click', () => {
        const hero = document.getElementById('photo-hero');
        if (!hero) return;
        hero.src = button.dataset.photoUrl;
        document.querySelectorAll('[data-photo-url]').forEach(other => other.classList.remove('selected'));
        button.classList.add('selected');
    });
});
</script>
<?php module_footer(); ?>
