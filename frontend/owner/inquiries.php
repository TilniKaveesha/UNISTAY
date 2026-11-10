<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/includes/unistay_module.php';
$ownerId = module_require('owner');
$pdo = db();
$path = '/owner/inquiries.php';
// Handle POST requests for approving or rejecting inquiries
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    module_check_csrf();// Validate CSRF token
    try {
        $id = module_positive_int($_POST['inquiry_id'] ?? null, 'inquiry ID');
        $action = (string)($_POST['action'] ?? '');
        if (!in_array($action, ['approve', 'reject'], true)) throw new InvalidArgumentException('Invalid decision.');
        $pdo->beginTransaction();
        $q = $pdo->prepare('SELECT i.status FROM `Inquiry` i INNER JOIN `BoardingListing` b ON b.listing_id = i.listing_id WHERE i.inquiry_id = ? AND b.owner_id = ? FOR UPDATE');
        $q->execute([$id, $ownerId]);
        $status = $q->fetchColumn();
        // Ensure the inquiry exists and is pending
        if ($status !== 'pending') throw new InvalidArgumentException('This inquiry is missing or no longer pending.');
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $q = $pdo->prepare('UPDATE `Inquiry` SET status = ? WHERE inquiry_id = ? AND status = ?');
        $q->execute([$newStatus, $id, 'pending']);
        // Ensure that exactly one row was updated
        if ($q->rowCount() !== 1) throw new RuntimeException('Inquiry changed during processing.');
        $pdo->commit();
        // Flash a success message
        module_flash('Inquiry #' . $id . ' ' . $newStatus . '.');
    } catch (InvalidArgumentException $e) {
        // Roll back the transaction if it's active and flash an error message
        if ($pdo->inTransaction()) $pdo->rollBack();
        module_flash($e->getMessage(), true);
    } catch (Throwable $e) {
        // Roll back the transaction if it's active and handle the error
        if ($pdo->inTransaction()) $pdo->rollBack();
        module_fail($e, $path);
    }
    module_redirect($path);
}
// Fetch inquiries for the current owner
$q = $pdo->prepare('SELECT i.inquiry_id, i.listing_id, i.student_user_id, i.message, i.status, u.full_name, u.email FROM `Inquiry` i INNER JOIN `BoardingListing` b ON b.listing_id = i.listing_id LEFT JOIN `User` u ON u.user_id = i.student_user_id WHERE b.owner_id = ? ORDER BY i.inquiry_id DESC');
$q->execute([$ownerId]);
$items = $q->fetchAll();
// Render the inquiries page
module_header('Student Inquiries', 'owner');
?>
<p class="muted">Approving an inquiry means you offer a place. A tenancy is created only if the student accepts the offer in their own account.</p>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Inquiry</th><th>Student</th><th>Listing</th><th>Message</th><th>Status</th><th>Decision</th></tr></thead><tbody>
<?php foreach ($items as $item): ?>
<tr><td>#<?= (int)$item['inquiry_id'] ?></td><td><?= module_escape((string)($item['full_name'] ?: 'Student')) ?><div class="muted small"><?= module_escape((string)$item['email']) ?></div></td><td>#<?= (int)$item['listing_id'] ?></td><td><?= nl2br(module_escape((string)$item['message'])) ?></td><td><span class="tag"><?= module_escape((string)$item['status']) ?></span></td><td>
<?php if ($item['status'] === 'pending'): ?><div class="actions">
<?php foreach (['approve' => 'Approve', 'reject' => 'Reject'] as $action => $label): ?>
<form method="post" class="inline" action="/owner/inquiries.php"><input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>"><input type="hidden" name="inquiry_id" value="<?= (int)$item['inquiry_id'] ?>"><input type="hidden" name="action" value="<?= $action ?>"><button class="<?= $action === 'reject' ? 'danger' : '' ?>" type="submit"><?= $label ?></button></form>
<?php endforeach; ?></div><?php else: ?><span class="muted">No action</span><?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="6" class="muted">No inquiries for your listings yet.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php module_footer(); ?>
