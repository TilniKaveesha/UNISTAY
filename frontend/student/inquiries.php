<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/includes/unistay_module.php';
$studentId = module_require('student');
$pdo = db();
$path = '/student/inquiries.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    module_check_csrf();
    try {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'create') {
            $listingId = module_positive_int($_POST['listing_id'] ?? null, 'listing ID');
            $message = trim((string)($_POST['message'] ?? ''));
            $length = function_exists('mb_strlen') ? mb_strlen($message, 'UTF-8') : strlen($message);
            if ($length < 5 || $length > 2000) {
                throw new InvalidArgumentException('Message must be between 5 and 2,000 characters.');
            }
            $pdo->beginTransaction();
            $q = $pdo->prepare('SELECT owner_id, availability, capacity FROM `BoardingListing` WHERE listing_id = ? FOR UPDATE');
            $q->execute([$listingId]);
            $listing = $q->fetch();
            if (!$listing || $listing['availability'] !== 'available' || (int)$listing['capacity'] < 1) {
                throw new InvalidArgumentException('This listing is not available for inquiries.');
            }
            if ((int)$listing['owner_id'] === $studentId) throw new InvalidArgumentException('You cannot inquire about your own listing.');
            $q = $pdo->prepare("SELECT COUNT(*) FROM `Tenancy` WHERE listing_id = ? AND status = 'active'");
            $q->execute([$listingId]);
            if ((int)$q->fetchColumn() >= (int)$listing['capacity']) throw new InvalidArgumentException('This listing is currently full.');
            $q = $pdo->prepare("SELECT COUNT(*) FROM `Inquiry` WHERE listing_id = ? AND student_user_id = ? AND status IN ('pending', 'approved', 'confirmed')");
            $q->execute([$listingId, $studentId]);
            if ((int)$q->fetchColumn() > 0) throw new InvalidArgumentException('You already have an open inquiry or tenancy for this listing.');
            $id = (int)$pdo->query('SELECT NEXTVAL(unistay_inquiry_seq)')->fetchColumn();
            $q = $pdo->prepare('INSERT INTO `Inquiry` (inquiry_id, student_user_id, listing_id, message, status) VALUES (?, ?, ?, ?, ?)');
            $q->execute([$id, $studentId, $listingId, $message, 'pending']);
            $pdo->commit();
            module_flash('Inquiry sent successfully! The owner can review it now.');
        } elseif ($action === 'withdraw') {
            $id = module_positive_int($_POST['inquiry_id'] ?? null, 'inquiry ID');
            $q = $pdo->prepare("UPDATE `Inquiry` SET status = 'withdrawn' WHERE inquiry_id = ? AND student_user_id = ? AND status = 'pending'");
            $q->execute([$id, $studentId]);
            module_flash($q->rowCount() === 1 ? 'Inquiry withdrawn.' : 'Only your pending inquiries can be withdrawn.', $q->rowCount() !== 1);
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

$q = $pdo->prepare('SELECT i.inquiry_id, i.listing_id, i.message, i.status,
    b.title AS listing_title, b.rent_amount, b.address, b.availability,
    (SELECT p.file_path FROM `ListingPhoto` p WHERE p.listing_id = b.listing_id ORDER BY p.is_cover DESC, p.photo_id ASC LIMIT 1) AS gallery_cover,
    b.cover_image_url
    FROM `Inquiry` i JOIN `BoardingListing` b ON b.listing_id = i.listing_id
    WHERE i.student_user_id = ? ORDER BY i.inquiry_id DESC');
$q->execute([$studentId]);
$all = $q->fetchAll();
$counts = ['all' => count($all), 'pending' => 0, 'approved' => 0, 'confirmed' => 0, 'rejected' => 0, 'withdrawn' => 0];
foreach ($all as $item) if (isset($counts[$item['status']])) $counts[$item['status']]++;
$filter = (string)($_GET['status'] ?? 'all');
if (!array_key_exists($filter, $counts)) $filter = 'all';
$items = $filter === 'all' ? $all : array_values(array_filter($all, static fn($row) => $row['status'] === $filter));

$selected = filter_var($_GET['listing_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$selectedListing = null;
$existingOpen = false;
$selectedCanInquire = false;
if ($selected) {
    $listingStmt = $pdo->prepare('SELECT b.listing_id, b.title, b.address, b.rent_amount, b.capacity, b.availability,
        u.name AS university_name,
        (SELECT COUNT(*) FROM `Tenancy` t WHERE t.listing_id = b.listing_id AND t.status = \'active\') AS occupied,
        (SELECT p.file_path FROM `ListingPhoto` p WHERE p.listing_id = b.listing_id ORDER BY p.is_cover DESC, p.photo_id ASC LIMIT 1) AS gallery_cover,
        b.cover_image_url
        FROM `BoardingListing` b LEFT JOIN `University` u ON u.university_id = b.university_id WHERE b.listing_id = ? LIMIT 1');
    $listingStmt->execute([(int)$selected]);
    $selectedListing = $listingStmt->fetch();
    if ($selectedListing) {
        $selectedCanInquire = $selectedListing['availability'] === 'available'
            && (int)$selectedListing['capacity'] > (int)$selectedListing['occupied'];
        $duplicateStmt = $pdo->prepare("SELECT COUNT(*) FROM `Inquiry` WHERE listing_id = ? AND student_user_id = ? AND status IN ('pending', 'approved', 'confirmed')");
        $duplicateStmt->execute([(int)$selected, $studentId]);
        $existingOpen = (int)$duplicateStmt->fetchColumn() > 0;
    }
}
module_header('My Inquiries', 'student');
?>
<p class="muted">Track your boarding requests and accept an approved offer when you're ready. Your account stays yours throughout the process.</p>
<?php if ($selected): ?>
  <?php if (!$selectedListing): ?><div class="notice error">This boarding listing could not be found.</div>
  <?php else: ?>
    <section class="panel inquiry-compose">
      <h2>Contact the owner</h2>
      <div class="inquiry-property-preview">
        <?php if ($selectedListing['gallery_cover'] ?: $selectedListing['cover_image_url']): ?>
          <img src="<?= module_escape((string)($selectedListing['gallery_cover'] ?: $selectedListing['cover_image_url'])) ?>" alt="Boarding photo" referrerpolicy="no-referrer">
        <?php endif; ?>
        <div><strong><?= module_escape((string)($selectedListing['title'] ?: ('Listing #' . $selectedListing['listing_id']))) ?></strong>
          <p class="muted small"><?= module_escape((string)$selectedListing['address']) ?></p>
          <p>LKR <?= module_escape((string)$selectedListing['rent_amount']) ?>/month · <?= module_escape((string)$selectedListing['university_name']) ?></p>
        </div>
      </div>
      <?php if (!$selectedCanInquire): ?>
        <p class="notice error">This boarding is not accepting new inquiries right now.</p>
      <?php elseif ($existingOpen): ?>
        <p class="notice success">You already have an open inquiry or tenancy for this boarding. Check its status below.</p>
      <?php else: ?>
        <form method="post" action="/student/inquiries.php">
          <input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>">
          <input type="hidden" name="action" value="create">
          <input type="hidden" name="listing_id" value="<?= (int)$selected ?>">
          <label for="message">Your message</label>
          <textarea name="message" id="message" required minlength="5" maxlength="2000" rows="5" placeholder="Hi! I'm interested in your boarding place. Could you tell me about room availability and house rules?"></textarea>
          <p class="muted small">Introduce yourself and ask about availability, facilities, or arranging a viewing. Never include bank details or passwords.</p>
          <div class="actions"><button type="submit">Send inquiry</button><a class="btn secondary" href="/student/listing.php?id=<?= (int)$selected ?>">Back to listing</a></div>
        </form>
      <?php endif; ?>
    </section>
  <?php endif; ?>
<?php endif; ?>
<div class="status-filters" aria-label="Filter inquiry history">
<?php foreach (['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'confirmed' => 'Confirmed', 'rejected' => 'Rejected', 'withdrawn' => 'Withdrawn'] as $value => $label): ?>
<a href="/student/inquiries.php?status=<?= $value ?>" class="status-pill <?= $filter === $value ? 'active' : '' ?>"><?= $label ?> <span><?= $counts[$value] ?></span></a>
<?php endforeach; ?>
</div>
<section class="inquiry-card-list">
<?php foreach ($items as $item): ?>
  <article class="panel inquiry-card">
    <div class="inquiry-card-top"><div>
      <span class="muted small">Inquiry #<?= (int)$item['inquiry_id'] ?></span>
      <h2><a class="plain-link" href="/student/listing.php?id=<?= (int)$item['listing_id'] ?>"><?= module_escape((string)($item['listing_title'] ?: ('Listing #' . $item['listing_id']))) ?></a></h2>
      <p class="muted small">LKR <?= module_escape((string)$item['rent_amount']) ?> / month</p>
    </div><span class="status-tag status-<?= module_escape((string)$item['status']) ?>"><?= module_escape(ucfirst((string)$item['status'])) ?></span></div>
    <p class="inquiry-message"><?= nl2br(module_escape((string)$item['message'])) ?></p>
    <div class="inquiry-card-bottom">
      <span class="muted small"><?php
        echo module_escape(match ($item['status']) {
          'pending' => 'Waiting for the owner to respond',
          'approved' => 'The owner approved your request. You can confirm a tenancy.',
          'confirmed' => 'Tenancy confirmed',
          'rejected' => 'The owner declined this request',
          'withdrawn' => 'You withdrew this request',
          default => 'See current status'
        });
      ?></span>
      <?php if ($item['status'] === 'pending'): ?>
      <form method="post" action="/student/inquiries.php" onsubmit="return confirm('Withdraw this inquiry?');">
        <input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>">
        <input type="hidden" name="action" value="withdraw">
        <input type="hidden" name="inquiry_id" value="<?= (int)$item['inquiry_id'] ?>">
        <button type="submit" class="secondary">Withdraw inquiry</button>
      </form>
      <?php elseif ($item['status'] === 'approved'): ?>
        <a class="btn" href="/student/tenancy.php">Review &amp; confirm offer</a>
      <?php elseif ($item['status'] === 'confirmed'): ?>
        <a class="btn secondary" href="/student/tenancy.php">View my tenancy</a>
      <?php endif; ?>
    </div>
  </article>
<?php endforeach; ?>
<?php if (!$items): ?><div class="panel muted">No inquiries match this filter. <a href="/student/listings.php">Browse boardings</a>.</div><?php endif; ?>
</section>
<?php module_footer(); ?>
