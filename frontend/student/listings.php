<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/includes/unistay_module.php';
$studentId = module_require('student');
$pdo = db();

// Database query disabled for commit
// $universities = $pdo->query('SELECT university_id, name, city FROM `University` ORDER BY name, city')->fetchAll();
$universityId = filter_var($_GET['university_id'] ?? null, FILTER_VALIDATE_INT);
$gender = (string)($_GET['gender'] ?? 'all');
$availability = (string)($_GET['availability'] ?? 'available');
$maxRent = trim((string)($_GET['max_rent'] ?? ''));
if (!in_array($gender, ['all', 'male', 'female'], true)) $gender = 'all';
if (!in_array($availability, ['available', 'soon', 'all'], true)) $availability = 'available';
if ($maxRent !== '' && (!ctype_digit($maxRent) || (int)$maxRent < 1 || (int)$maxRent > 99999999)) $maxRent = '';

$sql = 'SELECT b.listing_id, b.university_id, b.title, b.address, b.description, b.facilities,
    b.gender_category, b.available_from, b.cover_image_url, b.rent_amount, b.capacity, b.availability,
    u.name AS university_name, u.city AS university_city,
    (SELECT p.file_path FROM `ListingPhoto` p WHERE p.listing_id = b.listing_id ORDER BY p.is_cover DESC, p.photo_id ASC LIMIT 1) AS gallery_cover,
    (SELECT COUNT(*) FROM `Tenancy` t WHERE t.listing_id = b.listing_id AND t.status = \'active\') AS occupied
    FROM `BoardingListing` b
    LEFT JOIN `University` u ON b.university_id = u.university_id WHERE 1=1';
$bindings = [];
if ($universityId && $universityId > 0) { $sql .= ' AND b.university_id = ?'; $bindings[] = $universityId; }
if ($gender !== 'all') { $sql .= " AND b.gender_category IN ('any', ?)"; $bindings[] = $gender; }
if ($availability !== 'all') { $sql .= ' AND b.availability = ?'; $bindings[] = $availability; }
if ($maxRent !== '') { $sql .= ' AND b.rent_amount <= ?'; $bindings[] = $maxRent; }
$sql .= ' ORDER BY b.listing_id DESC LIMIT 100';
// Database query disabled for commit
// $q = $pdo->prepare($sql);
// Database query disabled for commit
// $q->execute($bindings);
$listings = $q->fetchAll();

module_header('Find a Boarding Place', 'student');
?>
<p class="muted">Compare boarding places near your university, then contact the owner through UniStay. Only your own student account can submit inquiries.</p>
<section class="panel">
  <form method="get" action="/student/listings.php" class="listing-filter">
    <div><label for="university_id">University</label><select id="university_id" name="university_id"><option value="">All universities</option>
      <?php foreach ($universities as $u): ?><option value="<?= (int)$u['university_id'] ?>" <?= $universityId && (int)$u['university_id'] === (int)$universityId ? 'selected' : '' ?>><?= module_escape((string)($u['name'] ?: ('University #' . $u['university_id']))) ?><?= $u['city'] ? ' · ' . module_escape((string)$u['city']) : '' ?></option><?php endforeach; ?>
    </select></div>
    <div><label for="gender">Accommodation category</label><select id="gender" name="gender">
      <?php foreach (['all' => 'All categories', 'male' => 'Male students', 'female' => 'Female students'] as $value => $label): ?><option value="<?= $value ?>" <?= $gender === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
    </select></div>
    <div><label for="max_rent">Maximum rent (LKR)</label><input id="max_rent" name="max_rent" type="number" min="1" max="99999999" placeholder="No limit" value="<?= module_escape($maxRent) ?>"></div>
    <div><label for="availability">Availability</label><select id="availability" name="availability">
      <?php foreach (['available' => 'Available now', 'soon' => 'Available soon', 'all' => 'Show all'] as $value => $label): ?><option value="<?= $value ?>" <?= $availability === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
    </select></div>
    <div class="filter-actions"><button type="submit">Search places</button><a href="/student/listings.php" class="btn secondary">Clear</a></div>
  </form>
</section>
<section class="cards listing-cards">
<?php $visible = 0; foreach ($listings as $row): ?>
  <?php
    $occupied = (int)$row['occupied'];
    $capacity = (int)$row['capacity'];
    $remaining = max(0, $capacity - $occupied);
    if ($availability === 'available' && $remaining === 0) continue;
    $visible++;
    $canInquire = $row['availability'] === 'available' && $remaining > 0;
  ?>
  <article class="card listing-card">
    <?php if ($row['gallery_cover'] ?: $row['cover_image_url']): ?><img class="listing-cover" src="<?= module_escape((string)($row['gallery_cover'] ?: $row['cover_image_url'])) ?>" alt="Boarding photo" loading="lazy" referrerpolicy="no-referrer"><?php else: ?><div class="listing-cover listing-placeholder">UniStay</div><?php endif; ?>
    <div class="listing-card-content">
      <span class="tag"><?= module_escape((string)($row['university_name'] ?: ('University #' . $row['university_id']))) ?></span>
      <h3><?= module_escape((string)($row['title'] ?: ('Boarding #' . $row['listing_id']))) ?></h3>
      <p class="muted small"><?= module_escape((string)($row['address'] ?: 'Address pending')) ?><?= $row['university_city'] ? ' · ' . module_escape((string)$row['university_city']) : '' ?></p>
      <p><strong>LKR <?= module_escape((string)$row['rent_amount']) ?></strong> <span class="muted">/ month</span></p>
      <p class="muted small"><?= $remaining ?> of <?= $capacity ?> places left · <?= module_escape((string)($row['gender_category'] === 'any' ? 'All students' : ucfirst((string)$row['gender_category']) . ' students')) ?></p>
      <?php if ($row['availability'] === 'soon' && $row['available_from']): ?><p class="muted small">Expected: <?= module_escape((string)$row['available_from']) ?></p><?php endif; ?>
      <p class="muted small"><?= module_escape((string)($row['description'] ? (function_exists('mb_substr') ? mb_substr((string)$row['description'], 0, 140) : substr((string)$row['description'], 0, 140)) : 'View details about this boarding place.')) ?></p>
      <div class="actions"><a class="btn secondary" href="/student/listing.php?id=<?= (int)$row['listing_id'] ?>">View details</a>
        <?php if ($canInquire): ?><a class="btn" href="/student/inquiries.php?listing_id=<?= (int)$row['listing_id'] ?>">Send inquiry</a><?php else: ?><span class="tag">Not taking inquiries</span><?php endif; ?>
      </div>
    </div>
  </article>
<?php endforeach; ?>
<?php if ($visible === 0): ?><div class="panel">No matching boarding places. Try clearing your filters.</div><?php endif; ?>
</section>
<?php module_footer(); ?>
