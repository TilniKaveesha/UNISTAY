<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/includes/rent_module.php';
$ownerId = module_require('owner');
$pdo = db();
$path = '/owner/tenants.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    module_check_csrf();
    try {
        if (($_POST['action'] ?? '') !== 'end') throw new InvalidArgumentException('Unknown action.');
        $id = module_positive_int($_POST['tenancy_id'] ?? null, 'tenancy ID');
        // Database query disabled for commit
        // $q = $pdo->prepare("UPDATE `Tenancy` t
            INNER JOIN `BoardingListing` b ON b.listing_id = t.listing_id
            SET t.status = 'ended', t.end_date = CURRENT_DATE()
            WHERE t.tenancy_id = ? AND b.owner_id = ? AND t.status = 'active'");
        // Database query disabled for commit
        // $q->execute([$id, $ownerId]);
        $updated = $q->rowCount() === 1;
        module_flash($updated ? 'Tenancy ended. Its rent and inquiry history remain available.' : 'No active tenancy was changed.', !$updated);
    } catch (InvalidArgumentException $e) {
        module_flash($e->getMessage(), true);
    } catch (Throwable $e) {
        module_fail($e, $path);
    }
    module_redirect($path);
}

// Database query disabled for commit
// $q = $pdo->prepare("SELECT t.tenancy_id, t.listing_id, t.start_date, t.end_date, t.agreed_rent, t.status,
        b.title AS listing_title, u.full_name, u.email,
        (SELECT COUNT(*) FROM `RentPayment` p WHERE p.tenancy_id = t.tenancy_id) AS bill_count,
        (SELECT COALESCE(SUM(p.amount_due - p.amount_paid), 0.00) FROM `RentPayment` p WHERE p.tenancy_id = t.tenancy_id) AS outstanding
    FROM `Tenancy` t
    INNER JOIN `BoardingListing` b ON b.listing_id = t.listing_id
    LEFT JOIN `User` u ON u.user_id = t.student_user_id
    WHERE b.owner_id = ? ORDER BY (t.status = 'active') DESC, t.tenancy_id DESC");
// Database query disabled for commit
// $q->execute([$ownerId]);
$all = $q->fetchAll();
$active = array_values(array_filter($all, static fn($t) => $t['status'] === 'active'));
$past = array_values(array_filter($all, static fn($t) => $t['status'] !== 'active'));
module_header('Tenancy Management & History', 'owner');
?>
<p class="muted">Tenancy is created only when the student confirms an approved inquiry. Ending it preserves the student's account, inquiry and rent history. Any outstanding rent remains visible.</p>
<div class="tenancy-overview"><div><span>Active tenancies</span><strong><?= count($active) ?></strong></div><div><span>Past tenancies</span><strong><?= count($past) ?></strong></div><div><span>Total tenancies</span><strong><?= count($all) ?></strong></div></div>
<?php foreach (['Active tenancies' => $active, 'Past tenancies' => $past] as $section => $items): ?>
<section class="panel"><h2><?= module_escape($section) ?></h2><div class="table-wrap"><table><thead><tr><th>Student</th><th>Boarding</th><th>Start / End</th><th>Rent</th><th>Outstanding</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php foreach ($items as $item): ?>
<tr>
 <td><strong><?= module_escape((string)($item['full_name'] ?: ('User #' . $item['tenancy_id']))) ?></strong><div class="muted small"><?= module_escape((string)$item['email']) ?></div></td>
 <td><?= module_escape((string)($item['listing_title'] ?: ('Listing #' . $item['listing_id']))) ?><div class="muted small">Tenancy #<?= (int)$item['tenancy_id'] ?></div></td>
 <td><?= module_escape((string)$item['start_date']) ?><div class="muted small">Ended: <?= module_escape((string)($item['end_date'] ?: '—')) ?></div></td>
 <td>LKR <?= module_escape((string)$item['agreed_rent']) ?></td>
 <td>LKR <?= module_escape(number_format((float)$item['outstanding'], 2, '.', ',')) ?><div class="muted small"><?= (int)$item['bill_count'] ?> monthly record(s)</div></td>
 <td><span class="tag"><?= module_escape(ucfirst((string)$item['status'])) ?></span></td>
 <td><div class="actions"><a class="btn secondary" href="/owner/rent.php?tenancy_id=<?= (int)$item['tenancy_id'] ?>">Rent records</a>
 <?php if ($item['status'] === 'active'): ?><form method="post" class="inline" action="/owner/tenants.php" onsubmit="return confirm('End tenancy? This keeps the account, inquiry and rent history.');"><input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>"><input type="hidden" name="tenancy_id" value="<?= (int)$item['tenancy_id'] ?>"><input type="hidden" name="action" value="end"><button class="danger" type="submit">End tenancy</button></form><?php endif; ?></div></td>
</tr>
<?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="7" class="muted">No <?= module_escape(strtolower($section)) ?> yet.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php endforeach; ?>
<?php module_footer(); ?>
