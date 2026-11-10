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
        $studentLock = $pdo->prepare('SELECT user_id FROM `User` WHERE user_id = ? FOR UPDATE');
        $studentLock->execute([$studentId]);
        if (!$studentLock->fetchColumn()) throw new InvalidArgumentException('Student account not found.');
        // Lock the listing row as the shared capacity mutex for all confirmations.
        $q = $pdo->prepare('SELECT listing_id FROM `Inquiry` WHERE inquiry_id = ? AND student_user_id = ?');
        $q->execute([$inquiryId, $studentId]);
        $listingId = $q->fetchColumn();
        if (!$listingId) throw new InvalidArgumentException('Inquiry not found.');
        $q = $pdo->prepare('SELECT listing_id, capacity, availability, rent_amount FROM `BoardingListing` WHERE listing_id = ? FOR UPDATE');
        $q->execute([(int)$listingId]);
        $listing = $q->fetch();
        if (!$listing || $listing['availability'] !== 'available' || (int)$listing['capacity'] < 1 || $listing['rent_amount'] === null) {
            throw new InvalidArgumentException('This listing cannot currently be confirmed.');
        }
        $q = $pdo->prepare('SELECT status, listing_id FROM `Inquiry` WHERE inquiry_id = ? AND student_user_id = ? FOR UPDATE');
        $q->execute([$inquiryId, $studentId]);
        $inquiry = $q->fetch();
        if (!$inquiry || $inquiry['status'] !== 'approved' || (int)$inquiry['listing_id'] !== (int)$listingId) {
            throw new InvalidArgumentException('The owner has not approved this inquiry or it has changed.');
        }
        $q = $pdo->prepare("SELECT COUNT(*) FROM `Tenancy` WHERE listing_id = ? AND status = 'active'");
        $q->execute([(int)$listingId]);
        if ((int)$q->fetchColumn() >= (int)$listing['capacity']) {
            throw new InvalidArgumentException('This listing is already at full capacity.');
        }
        $q = $pdo->prepare("SELECT COUNT(*) FROM `Tenancy` WHERE student_user_id = ? AND status = 'active'");
        $q->execute([$studentId]);
        if ((int)$q->fetchColumn() > 0) throw new InvalidArgumentException('You already have an active tenancy.');
        $q = $pdo->prepare('SELECT COUNT(*) FROM `Tenancy` WHERE inquiry_id = ?');
        $q->execute([$inquiryId]);
        if ((int)$q->fetchColumn() > 0) throw new InvalidArgumentException('This inquiry already has a tenancy.');
        $tenancyId = (int)$pdo->query('SELECT NEXTVAL(unistay_tenancy_seq)')->fetchColumn();
        $q = $pdo->prepare("INSERT INTO `Tenancy` (tenancy_id, inquiry_id, student_user_id, listing_id, start_date, agreed_rent, status) VALUES (?, ?, ?, ?, CURRENT_DATE(), ?, 'active')");
        $q->execute([$tenancyId, $inquiryId, $studentId, (int)$listingId, $listing['rent_amount']]);
        $q = $pdo->prepare("UPDATE `Inquiry` SET status = 'confirmed' WHERE inquiry_id = ? AND student_user_id = ? AND status = 'approved'");
        $q->execute([$inquiryId, $studentId]);
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
$q = $pdo->prepare("SELECT i.inquiry_id, i.listing_id, b.rent_amount FROM `Inquiry` i JOIN `BoardingListing` b ON b.listing_id = i.listing_id WHERE i.student_user_id = ? AND i.status = 'approved' ORDER BY i.inquiry_id DESC");
$q->execute([$studentId]);
$approved = $q->fetchAll();
$q = $pdo->prepare('SELECT tenancy_id, listing_id, start_date, agreed_rent, status FROM `Tenancy` WHERE student_user_id = ? ORDER BY tenancy_id DESC');
$q->execute([$studentId]);
$tenancies = $q->fetchAll();
module_header('My Tenancy', 'student');
?>
<section class="panel"><h2>Approved offers awaiting confirmation</h2>
<p class="muted small">Owner approval alone does not make you a tenant. You choose whether to accept. For the MVP, one student can have one active tenancy.</p>
<div class="table-wrap"><table><thead><tr><th>Inquiry</th><th>Listing</th><th>Current rent</th><th>Action</th></tr></thead><tbody>
<?php foreach ($approved as $item): ?><tr><td>#<?= (int)$item['inquiry_id'] ?></td><td>#<?= (int)$item['listing_id'] ?></td><td><?= module_escape((string)$item['rent_amount']) ?></td><td><form method="post" action="/student/tenancy.php" onsubmit="return confirm('Confirm this accommodation and its current monthly rent?');"><input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>"><input type="hidden" name="action" value="confirm"><input type="hidden" name="inquiry_id" value="<?= (int)$item['inquiry_id'] ?>"><button type="submit">Confirm tenancy</button></form></td></tr><?php endforeach; ?>
<?php if (!$approved): ?><tr><td colspan="4" class="muted">No approved offers awaiting confirmation.</td></tr><?php endif; ?>
</tbody></table></div></section>
<section class="panel"><h2>Tenancy history</h2><div class="table-wrap"><table><thead><tr><th>Tenancy</th><th>Listing</th><th>Started</th><th>Agreed rent</th><th>Status</th></tr></thead><tbody>
<?php foreach ($tenancies as $item): ?><tr><td>#<?= (int)$item['tenancy_id'] ?></td><td>#<?= (int)$item['listing_id'] ?></td><td><?= module_escape((string)$item['start_date']) ?></td><td><?= module_escape((string)$item['agreed_rent']) ?></td><td><span class="tag"><?= module_escape((string)$item['status']) ?></span></td></tr><?php endforeach; ?>
<?php if (!$tenancies): ?><tr><td colspan="5" class="muted">No tenancy record yet.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php module_footer(); ?>
