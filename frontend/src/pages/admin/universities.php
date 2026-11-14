<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../../backend/includes/admin_ui.php';

$pdo = ua_bootstrap();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ua_check_post();
    try {
        if (($_POST['action'] ?? '') !== 'add_university') {
            throw new InvalidArgumentException('Unsupported action.');
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        if ($name === '' || $city === '' || strlen($name) > 150 || strlen($city) > 100) {
            throw new InvalidArgumentException('Enter a university name (up to 150 characters) and city (up to 100 characters).');
        }
        // Match the existing schema: University(university_id, name, city).
        // No `address` column or AUTO_INCREMENT is assumed.
        $dupe = $pdo->prepare('SELECT university_id FROM `University` WHERE LOWER(name) = LOWER(?) AND LOWER(city) = LOWER(?) LIMIT 1');
        $dupe->execute([$name, $city]);
        if ($dupe->fetchColumn()) {
            throw new InvalidArgumentException('This university and city combination already exists.');
        }
        // IDs are explicit on the current UniStay schema. Retry if another writer
        // inserts the same ID between MAX() and INSERT. A DB sequence is preferable
        // for larger scale deployments.
        $inserted = false;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $maxId = (int) $pdo->query('SELECT COALESCE(MAX(university_id), 0) FROM `University`')->fetchColumn();
            if ($maxId >= 2147483647) {
                throw new RuntimeException('The university ID limit has been reached.');
            }
            try {
                $stmt = $pdo->prepare('INSERT INTO `University` (university_id, name, city) VALUES (?, ?, ?)');
                $stmt->execute([$maxId + 1, $name, $city]);
                $inserted = true;
                break;
            } catch (PDOException $e) {
                if ((int) ($e->errorInfo[1] ?? 0) !== 1062 || $attempt === 2) throw $e;
            }
        }
        if (!$inserted) throw new RuntimeException('Unable to allocate a unique university ID.');
        ua_flash('University added to the UniStay directory.');
    } catch (InvalidArgumentException $e) {
        ua_flash($e->getMessage(), 'error');
    } catch (Throwable $e) {
        error_log('Admin university creation failed: ' . $e->getMessage());
        ua_flash('Could not add the university. Check the database setup.', 'error');
    }
    ua_redirect('universities.php');
}

$q = $_GET['q'] ?? '';
$q = is_string($q) ? trim($q) : '';
$q = substr($q, 0, 100);
$page = ua_page_number($_GET['page'] ?? null);
$pageSize = 15;
$params = [];
$whereSql = '';
if ($q !== '') {
    $whereSql = ' WHERE name LIKE :q_name OR city LIKE :q_city';
    $params[':q_name'] = '%' . $q . '%';
    $params[':q_city'] = '%' . $q . '%';
}
try {
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM `University`' . $whereSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $page = min($page, max(1, (int) ceil($total / $pageSize)));
    $offset = ($page - 1) * $pageSize;
    $stmt = $pdo->prepare('SELECT university_id, name, city FROM `University`' . $whereSql . ' ORDER BY name ASC, university_id ASC LIMIT ' . $pageSize . ' OFFSET ' . $offset);
    $stmt->execute($params);
    $universities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $dataError = false;
} catch (Throwable $e) {
    error_log('Admin universities list failed: ' . $e->getMessage());
    $universities = []; $total = 0; $dataError = true;
}

ua_page_start('universities', 'Universities', 'Maintain the university directory used in student accommodation searches.');
?>
<section class="ua-panel ua-table-panel">
    <div class="ua-panel-heading ua-panel-heading-wrap"><div><h2>University directory</h2><p><?= number_format($total) ?> university record(s)</p></div><button type="button" class="ua-button ua-button-primary" data-open-dialog="ua-add-uni"><?= ua_icon('plus', 18) ?> Add University</button></div>
    <form class="ua-filters" method="get" action="/public/admin/universities.php">
        <label class="ua-search-field"><?= ua_icon('search', 18) ?><input name="q" type="search" placeholder="Search university or city" value="<?= ua_escape($q) ?>" aria-label="Search universities"></label>
        <button type="submit" class="ua-button ua-button-primary"><?= ua_icon('filter', 16) ?> Filter</button>
        <a href="/public/admin/universities.php" class="ua-button ua-button-outline">Reset</a>
    </form>
    <?php if ($dataError): ?><div class="ua-alert ua-alert-error" role="alert">Unable to load universities. Check the server log.</div><?php endif; ?>
    <div class="ua-table-scroll"><table class="ua-table ua-table-spacious"><thead><tr><th>University</th><th>City</th><th>ID</th><th>Used for</th></tr></thead><tbody>
        <?php foreach ($universities as $university): ?>
            <tr>
                <td><div class="ua-user-cell"><span class="ua-table-avatar ua-table-avatar-violet"><?= ua_icon('university', 18) ?></span><strong><?= ua_escape($university['name']) ?></strong></div></td>
                <td><?= ua_escape($university['city'] ?: 'Not specified') ?></td>
                <td class="ua-mono">#<?= (int) $university['university_id'] ?></td>
                <td><span class="ua-muted-small">Boarding search filter</span></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$universities): ?><tr><td colspan="4" class="ua-empty-cell">No universities match your search.</td></tr><?php endif; ?>
    </tbody></table></div>
    <?php ua_pager($page, $total, $pageSize, ['q' => $q]); ?>
</section>

<dialog id="ua-add-uni" class="ua-dialog" aria-labelledby="ua-dialog-title">
    <div class="ua-dialog-head"><div><h2 id="ua-dialog-title">Add a university</h2><p>Make a new university available in public boarding search.</p></div><button class="ua-icon-btn" type="button" data-close-dialog aria-label="Close dialog"><?= ua_icon('close', 18) ?></button></div>
    <form method="post" action="/public/admin/universities.php" class="ua-dialog-form">
        <input type="hidden" name="csrf_token" value="<?= ua_escape(ua_token()) ?>">
        <input type="hidden" name="action" value="add_university">
        <label>University name <input type="text" name="name" maxlength="150" placeholder="e.g. University of Moratuwa" required autocomplete="off"></label>
        <label>City <input type="text" name="city" maxlength="100" placeholder="e.g. Moratuwa" required autocomplete="address-level2"></label>
        <p class="ua-form-help">The current UniStay database stores university name and city. No address field is required.</p>
        <div class="ua-dialog-actions"><button type="button" class="ua-button ua-button-outline" data-close-dialog>Cancel</button><button type="submit" class="ua-button ua-button-primary"><?= ua_icon('plus', 16) ?> Add University</button></div>
    </form>
</dialog>
<?php ua_page_end(); ?>
