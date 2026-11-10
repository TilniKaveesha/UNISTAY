<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/includes/unistay_module.php';
$ownerId = module_require('owner');
$pdo = db();
$path = '/owner/tenants.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    module_check_csrf();
    try {
        $id = module_positive_int($_POST['tenancy_id'] ?? null, 'tenancy ID');
        if (($_POST['action'] ?? '') !== 'end') throw new InvalidArgumentException('Unknown action.');
        $q = $pdo->prepare("UPDATE `Tenancy` t INNER JOIN `BoardingListing` b ON b.listing_id = t.listing_id SET t.status = 'ended' WHERE t.tenancy_id = ? AND b.owner_id = ? AND t.status = 'active'");
        $q->execute([$id, $ownerId]);
        module_flash($q->rowCount() === 1 ? 'Tenancy ended; history has been preserved.' : 'No active tenancy was changed.', $q->rowCount() !== 1);
    } catch (InvalidArgumentException $e) {
        module_flash($e->getMessage(), true);
    } catch (Throwable $e) {
        module_fail($e, $path);
    }
    module_redirect($path);
}
$q = $pdo->prepare('SELECT t.tenancy_id, t.listing_id, t.start_date, t.agreed_rent, t.status, u.full_name, u.email FROM `Tenancy` t INNER JOIN `BoardingListing` b ON b.listing_id = t.listing_id LEFT JOIN `User` u ON u.user_id = t.student_user_id WHERE b.owner_id = ? ORDER BY t.tenancy_id DESC');
$q->execute([$ownerId]);
$items = $q->fetchAll();
module_header('Tenancies', 'owner');
?>
<p class="muted">Only confirmed student inquiries create a tenancy. Ending a tenancy does not delete the student or payment history.</p>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Tenancy</th><th>Student</th><th>Listing</th><th>Start</th><th>Agreed rent</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php foreach ($items as $item): ?><tr><td>#<?= (int)$item['tenancy_id'] ?></td><td><?= module_escape((string)$item['full_name']) ?><div class="muted small"><?= module_escape((string)$item['email']) ?></div></td><td>#<?= (int)$item['listing_id'] ?></td><td><?= module_escape((string)$item['start_date']) ?></td><td><?= module_escape((string)$item['agreed_rent']) ?></td><td><span class="tag"><?= module_escape((string)$item['status']) ?></span></td><td>
<?php if ($item['status'] === 'active'): ?><form method="post" class="inline" action="/owner/tenants.php" onsubmit="return confirm('End this tenancy? This does not delete payment history.');"><input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>"><input type="hidden" name="tenancy_id" value="<?= (int)$item['tenancy_id'] ?>"><input type="hidden" name="action" value="end"><button class="danger" type="submit">End</button></form><?php else: ?><span class="muted">Closed</span><?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="7" class="muted">No confirmed tenancies yet.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php module_footer(); ?>
