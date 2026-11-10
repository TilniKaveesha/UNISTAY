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
            if (strlen($message) < 5 || strlen($message) > 8000) {
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
            module_flash('Inquiry sent to the owner!');
        } elseif ($action === 'withdraw') {
            $id = module_positive_int($_POST['inquiry_id'] ?? null, 'inquiry ID');
            $q = $pdo->prepare("UPDATE `Inquiry` SET status = 'withdrawn' WHERE inquiry_id = ? AND student_user_id = ? AND status = 'pending'");
            $q->execute([$id, $studentId]);
            module_flash($q->rowCount() === 1 ? 'Inquiry withdrawn.' : 'Only your pending inquiry can be withdrawn.', $q->rowCount() !== 1);
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
$q = $pdo->prepare('SELECT inquiry_id, listing_id, message, status FROM `Inquiry` WHERE student_user_id = ? ORDER BY inquiry_id DESC');
$q->execute([$studentId]);
$items = $q->fetchAll();
$selected = filter_var($_GET['listing_id'] ?? null, FILTER_VALIDATE_INT);
module_header('My Inquiries', 'student');
?>
<?php if ($selected): ?>
<section class="panel"><h2>Send an inquiry for listing #<?= (int)$selected ?></h2>
<form method="post" action="/student/inquiries.php"><input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>"><input type="hidden" name="action" value="create"><input type="hidden" name="listing_id" value="<?= (int)$selected ?>">
<label for="message">Message to the owner</label><textarea name="message" id="message" required minlength="5" maxlength="2000" placeholder="Hello, I'm interested in this boarding place. Is there a vacancy?"></textarea>
<div class="actions" style="margin-top:12px"><button type="submit">Submit inquiry</button><a class="btn secondary" href="/student/listings.php">Cancel</a></div>
</form></section>
<?php endif; ?>
<section class="panel"><h2>Inquiry history</h2><div class="table-wrap"><table><thead><tr><th>ID</th><th>Listing</th><th>Message</th><th>Status</th><th>Next step</th></tr></thead><tbody>
<?php foreach ($items as $item): ?><tr><td>#<?= (int)$item['inquiry_id'] ?></td><td>#<?= (int)$item['listing_id'] ?></td><td><?= nl2br(module_escape((string)$item['message'])) ?></td><td><span class="tag"><?= module_escape((string)$item['status']) ?></span></td><td>
<?php if ($item['status'] === 'pending'): ?><form method="post" class="inline" action="/student/inquiries.php"><input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>"><input type="hidden" name="action" value="withdraw"><input type="hidden" name="inquiry_id" value="<?= (int)$item['inquiry_id'] ?>"><button type="submit" class="secondary">Withdraw</button></form>
<?php elseif ($item['status'] === 'approved'): ?><a class="btn" href="/student/tenancy.php">Confirm tenancy</a><?php else: ?>—<?php endif; ?>
</td></tr><?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="5" class="muted">You haven't sent any inquiries yet. <a href="/student/listings.php">Browse listings</a>.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php module_footer(); ?>
