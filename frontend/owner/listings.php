<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/includes/unistay_module.php';
$ownerId = module_require('owner');
$pdo = db();
$path = '/owner/listings.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    module_check_csrf();
    try {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'create' || $action === 'update') {
            $universityId = module_positive_int($_POST['university_id'] ?? null, 'university ID');
            $capacity = module_positive_int($_POST['capacity'] ?? null, 'capacity');
            if ($capacity > 10000) throw new InvalidArgumentException('Capacity is too large.');
            $rent = module_money($_POST['rent_amount'] ?? '');
            $availability = (string)($_POST['availability'] ?? '');
            if (!in_array($availability, ['available', 'soon', 'unavailable'], true)) {
                throw new InvalidArgumentException('Choose a valid availability status.');
            }
            $check = $pdo->prepare('SELECT university_id FROM `University` WHERE university_id = ? LIMIT 1');
            $check->execute([$universityId]);
            if (!$check->fetchColumn()) throw new InvalidArgumentException('That university ID does not exist.');
            if ($action === 'create') {
                $id = (int)$pdo->query('SELECT NEXTVAL(unistay_listing_seq)')->fetchColumn();
                $q = $pdo->prepare('INSERT INTO `BoardingListing` (listing_id, owner_id, university_id, rent_amount, capacity, availability) VALUES (?, ?, ?, ?, ?, ?)');
                $q->execute([$id, $ownerId, $universityId, $rent, $capacity, $availability]);
                module_flash('Listing #' . $id . ' created successfully.');
            } else {
                $id = module_positive_int($_POST['listing_id'] ?? null, 'listing ID');
                $pdo->beginTransaction();
                $lock = $pdo->prepare('SELECT listing_id FROM `BoardingListing` WHERE listing_id = ? AND owner_id = ? FOR UPDATE');
                $lock->execute([$id, $ownerId]);
                if (!$lock->fetchColumn()) throw new InvalidArgumentException('Listing not found or not yours.');
                $count = $pdo->prepare("SELECT COUNT(*) FROM `Tenancy` WHERE listing_id = ? AND status = 'active'");
                $count->execute([$id]);
                if ((int)$count->fetchColumn() > $capacity) throw new InvalidArgumentException('Capacity cannot be below current active occupancy.');
                $q = $pdo->prepare('UPDATE `BoardingListing` SET university_id = ?, rent_amount = ?, capacity = ?, availability = ? WHERE listing_id = ? AND owner_id = ?');
                $q->execute([$universityId, $rent, $capacity, $availability, $id, $ownerId]);
                $pdo->commit();
                module_flash('Listing saved.');
            }
        } elseif ($action === 'deactivate') {
            $id = module_positive_int($_POST['listing_id'] ?? null, 'listing ID');
            $q = $pdo->prepare("UPDATE `BoardingListing` SET availability = 'unavailable' WHERE listing_id = ? AND owner_id = ?");
            $q->execute([$id, $ownerId]);
            module_flash('Listing marked unavailable; existing history is preserved.');
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

$q = $pdo->prepare('SELECT b.listing_id, b.university_id, b.rent_amount, b.capacity, b.availability, u.name AS university_name, u.city AS university_city FROM `BoardingListing` b LEFT JOIN `University` u ON u.university_id = b.university_id WHERE b.owner_id = ? ORDER BY b.listing_id DESC');
$q->execute([$ownerId]);
$listings = $q->fetchAll();
// Only offer universities that actually exist in the database.
$universities = $pdo->query('SELECT university_id, name, city FROM `University` ORDER BY name, city, university_id')->fetchAll(PDO::FETCH_ASSOC);
$edit = null;
$requested = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
if ($requested) {
    foreach ($listings as $row) {
        if ((int)$row['listing_id'] === (int)$requested) { $edit = $row; break; }
    }
}
module_header('My Boarding Listings', 'owner');
?>
<p class="muted">Create and manage listings. Choose a university already registered in TiDB.</p>
<?php if (!$universities): ?>
    <div class="notice error">No universities have been added yet. Ask the database team to add rows to the <code>University</code> table before creating a listing.</div>
<?php endif; ?>
<section class="panel">
    <h2><?= $edit ? 'Edit Listing #' . (int)$edit['listing_id'] : 'Create a listing' ?></h2>
    <form method="post" action="/owner/listings.php">
        <input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>">
        <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
        <?php if ($edit): ?><input type="hidden" name="listing_id" value="<?= (int)$edit['listing_id'] ?>"><?php endif; ?>
        <div class="grid">
            <div>
                <label for="university_id">University</label>
                <select id="university_id" name="university_id" required <?= !$universities ? 'disabled' : '' ?>>
                    <option value="">Select a university</option>
                    <?php foreach ($universities as $university): ?>
                        <?php $uid = (int)$university['university_id']; ?>
                        <option value="<?= $uid ?>" <?= isset($edit['university_id']) && (int)$edit['university_id'] === $uid ? 'selected' : '' ?>><?= module_escape((string)($university['name'] ?: ('University #' . $uid))) ?><?= !empty($university['city']) ? ' — ' . module_escape((string)$university['city']) : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label for="rent_amount">Monthly rent amount</label><input id="rent_amount" type="number" name="rent_amount" min="0.01" max="99999999" step="0.01" required value="<?= module_escape(isset($edit['rent_amount']) ? (string)$edit['rent_amount'] : '') ?>"></div>
            <div><label for="capacity">Number of places (capacity)</label><input id="capacity" type="number" name="capacity" min="1" max="10000" required value="<?= module_escape(isset($edit['capacity']) ? (string)$edit['capacity'] : '1') ?>"></div>
            <div><label for="availability">Availability</label><select id="availability" name="availability">
                <?php foreach (['available' => 'Available', 'soon' => 'Soon', 'unavailable' => 'Unavailable'] as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($edit['availability'] ?? 'available') === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select></div>
        </div>
        <div class="actions" style="margin-top:15px"><button type="submit" <?= !$universities ? 'disabled' : '' ?>><?= $edit ? 'Save changes' : 'Create listing' ?></button><?php if ($edit): ?><a class="btn secondary" href="/owner/listings.php">Cancel edit</a><?php endif; ?></div>
    </form>
</section>
<section class="panel"><h2>Your listings</h2>
    <div class="table-wrap"><table><thead><tr><th>ID</th><th>University</th><th>Rent</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($listings as $row): ?>
        <tr><td>#<?= (int)$row['listing_id'] ?></td><td><?= module_escape((string)($row['university_name'] ?: ('University #' . $row['university_id']))) ?><?= !empty($row['university_city']) ? ' — ' . module_escape((string)$row['university_city']) : '' ?></td><td><?= module_escape((string)$row['rent_amount']) ?></td><td><?= (int)$row['capacity'] ?></td><td><span class="tag"><?= module_escape((string)$row['availability']) ?></span></td><td><div class="actions">
        <a class="btn secondary" href="/owner/listings.php?edit=<?= (int)$row['listing_id'] ?>">Edit</a>
        <?php if ($row['availability'] !== 'unavailable'): ?><form method="post" class="inline" action="/owner/listings.php"><input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>"><input type="hidden" name="action" value="deactivate"><input type="hidden" name="listing_id" value="<?= (int)$row['listing_id'] ?>"><button type="submit" class="danger">Deactivate</button></form><?php endif; ?>
        </div></td></tr>
    <?php endforeach; ?>
    <?php if (!$listings): ?><tr><td colspan="6" class="muted">You haven't created any listings yet.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php module_footer(); ?>
