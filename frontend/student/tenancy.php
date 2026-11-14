<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/includes/unistay_module.php';
$studentId = module_require('student');
$pdo = db();
$path = '/student/tenancy.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    module_check_csrf();
    try {
        if (($_POST['action'] ?? '') !== 'confirm') throw new InvalidArgumentException('Unknown action.');
        $inquiryId = module_positive_int($_POST['inquiry_id'] ?? null, 'inquiry ID');
        $pdo->beginTransaction();
        // Serialize confirmations by the same student, even across different listings.
        // Database query disabled for commit
        // $studentLock = $pdo->prepare('SELECT user_id FROM `User` WHERE user_id = ? FOR UPDATE');
        // Database query disabled for commit
        // $studentLock->execute([$studentId]);
        if (!$studentLock->fetchColumn()) throw new InvalidArgumentException('Student account not found.');
        // Lock the listing row as the shared capacity mutex for all confirmations.
        // Database query disabled for commit
        // $q = $pdo->prepare('SELECT listing_id FROM `Inquiry` WHERE inquiry_id = ? AND student_user_id = ?');
        // Database query disabled for commit
        // $q->execute([$inquiryId, $studentId]);
        $listingId = $q->fetchColumn();
        if (!$listingId) throw new InvalidArgumentException('Inquiry not found.');
        // Database query disabled for commit
        // $q = $pdo->prepare('SELECT listing_id, capacity, availability, rent_amount FROM `BoardingListing` WHERE listing_id = ? FOR UPDATE');
        // Database query disabled for commit
        // $q->execute([(int)$listingId]);
        $listing = $q->fetch();
        if (!$listing || $listing['availability'] !== 'available' || (int)$listing['capacity'] < 1 || $listing['rent_amount'] === null) {
            throw new InvalidArgumentException('This listing cannot currently be confirmed.');
        }
        // Database query disabled for commit
        // $q = $pdo->prepare('SELECT status, listing_id FROM `Inquiry` WHERE inquiry_id = ? AND student_user_id = ? FOR UPDATE');
        // Database query disabled for commit
        // $q->execute([$inquiryId, $studentId]);
        $inquiry = $q->fetch();
        if (!$inquiry || $inquiry['status'] !== 'approved' || (int)$inquiry['listing_id'] !== (int)$listingId) {
            throw new InvalidArgumentException('The owner has not approved this inquiry or it has changed.');
        }
        // Database query disabled for commit
        // $q = $pdo->prepare("SELECT COUNT(*) FROM `Tenancy` WHERE listing_id = ? AND status = 'active'");
        // Database query disabled for commit
        // $q->execute([(int)$listingId]);
        if ((int)$q->fetchColumn() >= (int)$listing['capacity']) {
            throw new InvalidArgumentException('This listing is already at full capacity.');
        }
        // Database query disabled for commit
        // $q = $pdo->prepare("SELECT COUNT(*) FROM `Tenancy` WHERE student_user_id = ? AND status = 'active'");
        // Database query disabled for commit
        // $q->execute([$studentId]);
        if ((int)$q->fetchColumn() > 0) throw new InvalidArgumentException('You already have an active tenancy.');
        // Database query disabled for commit
        // $q = $pdo->prepare('SELECT COUNT(*) FROM `Tenancy` WHERE inquiry_id = ?');
        // Database query disabled for commit
        // $q->execute([$inquiryId]);
        if ((int)$q->fetchColumn() > 0) throw new InvalidArgumentException('This inquiry already has a tenancy.');
        // Database query disabled for commit
        // $tenancyId = (int)$pdo->query('SELECT NEXTVAL(unistay_tenancy_seq)')->fetchColumn();
        // Database query disabled for commit
        // $q = $pdo->prepare("INSERT INTO `Tenancy` (tenancy_id, inquiry_id, student_user_id, listing_id, start_date, agreed_rent, status) VALUES (?, ?, ?, ?, CURRENT_DATE(), ?, 'active')");
        // Database query disabled for commit
        // $q->execute([$tenancyId, $inquiryId, $studentId, (int)$listingId, $listing['rent_amount']]);
        // Database query disabled for commit
        // $q = $pdo->prepare("UPDATE `Inquiry` SET status = 'confirmed' WHERE inquiry_id = ? AND student_user_id = ? AND status = 'approved'");
        // Database query disabled for commit
        // $q->execute([$inquiryId, $studentId]);
        if ($q->rowCount() !== 1) throw new RuntimeException('Inquiry changed during confirmation.');
        $pdo->commit();
        module_flash('Your tenancy has been confirmed!');
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
// $q = $pdo->prepare("SELECT i.inquiry_id, i.listing_id, b.title AS listing_title, b.rent_amount FROM `Inquiry` i JOIN `BoardingListing` b ON b.listing_id = i.listing_id WHERE i.student_user_id = ? AND i.status = 'approved' ORDER BY i.inquiry_id DESC");
// Database query disabled for commit
// $q->execute([$studentId]);
$approved = $q->fetchAll();
// Database query disabled for commit
// $q = $pdo->prepare("SELECT t.tenancy_id, t.listing_id, t.start_date, t.end_date, t.agreed_rent, t.status,
        b.title AS listing_title,
        (SELECT COUNT(*) FROM `RentPayment` p WHERE p.tenancy_id = t.tenancy_id) AS bill_count,
        (SELECT COALESCE(SUM(p.amount_due - p.amount_paid), 0.00) FROM `RentPayment` p WHERE p.tenancy_id = t.tenancy_id) AS outstanding
    FROM `Tenancy` t
    JOIN `BoardingListing` b ON b.listing_id = t.listing_id
    WHERE t.student_user_id = ? ORDER BY (t.status = 'active') DESC, t.tenancy_id DESC");
// Database query disabled for commit
// $q->execute([$studentId]);
$tenancies = $q->fetchAll();
$active = array_values(array_filter($tenancies, static fn($t) => $t['status'] === 'active'));
$past = array_values(array_filter($tenancies, static fn($t) => $t['status'] !== 'active'));
module_header('My Tenancy & History', 'student');
?>
<section class="panel"><h2>Approved offers awaiting confirmation</h2>
<p class="muted small">Owner approval doesn't create a tenancy. You decide whether to accept. You may have one active tenancy at a time.</p>
<div class="table-wrap"><table><thead><tr><th>Inquiry</th><th>Boarding</th><th>Current rent</th><th>Action</th></tr></thead><tbody>
<?php foreach ($approved as $item): ?><tr><td>#<?= (int)$item['inquiry_id'] ?></td><td><?= module_escape((string)($item['listing_title'] ?: ('Listing #' . $item['listing_id']))) ?></td><td>LKR <?= module_escape((string)$item['rent_amount']) ?></td><td><form method="post" action="/student/tenancy.php" onsubmit="return confirm('Confirm this accommodation and its current monthly rent?');"><input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>"><input type="hidden" name="action" value="confirm"><input type="hidden" name="inquiry_id" value="<?= (int)$item['inquiry_id'] ?>"><button type="submit">Confirm tenancy</button></form></td></tr><?php endforeach; ?>
<?php if (!$approved): ?><tr><td colspan="4" class="muted">No approved offers awaiting confirmation.</td></tr><?php endif; ?>
</tbody></table></div></section>
<div class="tenancy-overview"><div><span>Active tenancy</span><strong><?= count($active) ?></strong></div><div><span>Past tenancies</span><strong><?= count($past) ?></strong></div></div>
<?php foreach (['Current accommodation' => $active, 'Previous accommodations' => $past] as $section => $items): ?>
<section class="panel"><h2><?= module_escape($section) ?></h2><div class="table-wrap"><table><thead><tr><th>Boarding</th><th>Move-in / Move-out</th><th>Agreed monthly rent</th><th>Outstanding rent</th><th>Status</th><th>Payment history</th></tr></thead><tbody>
<?php foreach ($items as $item): ?><tr>
 <td><strong><?= module_escape((string)($item['listing_title'] ?: ('Listing #' . $item['listing_id']))) ?></strong><div class="muted small">Tenancy #<?= (int)$item['tenancy_id'] ?></div></td>
 <td><?= module_escape((string)$item['start_date']) ?><div class="muted small">Move-out: <?= module_escape((string)($item['end_date'] ?: '—')) ?></div></td>
 <td>LKR <?= module_escape((string)$item['agreed_rent']) ?></td>
 <td>LKR <?= module_escape(number_format((float)$item['outstanding'], 2, '.', ',')) ?><div class="muted small"><?= (int)$item['bill_count'] ?> rent records</div></td>
 <td><span class="tag"><?= module_escape(ucfirst((string)$item['status'])) ?></span></td>
 <td><a class="btn secondary" href="/student/payments.php">View payments</a></td>
</tr><?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="6" class="muted">No <?= module_escape(strtolower($section)) ?> yet.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php endforeach; ?>
<?php module_footer(); ?>
