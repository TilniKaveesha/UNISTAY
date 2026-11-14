<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../../backend/includes/admin_ui.php';

$pdo = ua_bootstrap();

// Only admins can mutate account status. Never permit changing an admin account here.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ua_check_post();
    try {
        $id = ua_valid_positive_id($_POST['user_id'] ?? null);
        $status = $_POST['status'] ?? null;
        if (!is_string($status) || !in_array($status, ['active', 'suspended'], true)) {
            throw new InvalidArgumentException('Select a valid status.');
        }
        $stmt = $pdo->prepare("UPDATE `User` SET status = ? WHERE user_id = ? AND role IN ('student','owner')");
        $stmt->execute([$status, $id]);
        ua_flash($stmt->rowCount() ? 'Account status updated.' : 'No account was changed. Admin accounts cannot be suspended here.', $stmt->rowCount() ? 'success' : 'error');
    } catch (InvalidArgumentException $e) {
        ua_flash($e->getMessage(), 'error');
    } catch (Throwable $e) {
        error_log('Admin user update failed: ' . $e->getMessage());
        ua_flash('Unable to update the account. Please try again.', 'error');
    }
    ua_redirect('users.php');
}

$q = $_GET['q'] ?? '';
$q = is_string($q) ? trim($q) : '';
$q = substr($q, 0, 100);
$role = $_GET['role'] ?? '';
$role = is_string($role) ? trim($role) : '';
$status = $_GET['status'] ?? '';
$status = is_string($status) ? trim($status) : '';
if (!in_array($role, ['', 'student', 'owner', 'admin'], true)) $role = '';
if (!in_array($status, ['', 'active', 'suspended'], true)) $status = '';
$page = ua_page_number($_GET['page'] ?? null);
$pageSize = 15;

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(full_name LIKE :name_query OR email LIKE :email_query)';
    $params[':name_query'] = '%' . $q . '%';
    $params[':email_query'] = '%' . $q . '%';
}
if ($role !== '') { $where[] = 'role = :role'; $params[':role'] = $role; }
if ($status !== '') { $where[] = 'status = :status'; $params[':status'] = $status; }
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

try {
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM `User`' . $whereSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $page = min($page, max(1, (int) ceil($total / $pageSize)));
    $offset = ($page - 1) * $pageSize;
    $stmt = $pdo->prepare('SELECT user_id, full_name, email, role, status FROM `User`' . $whereSql . ' ORDER BY user_id DESC LIMIT ' . $pageSize . ' OFFSET ' . $offset);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $dataError = false;
} catch (Throwable $e) {
    error_log('Admin user list failed: ' . $e->getMessage());
    $users = []; $total = 0; $dataError = true;
}

ua_page_start('users', 'Manage Users', 'Review student and owner accounts and manage access safely.');
?>
<section class="ua-panel ua-table-panel">
    <div class="ua-panel-heading ua-panel-heading-wrap"><div><h2>All accounts</h2><p><?= number_format($total) ?> results<?= $q !== '' ? ' matching your search' : '' ?></p></div><span class="ua-label-icon"><?= ua_icon('shield', 18) ?> Admin accounts are protected</span></div>
    <form class="ua-filters" method="get" action="/public/admin/users.php">
        <label class="ua-search-field"><?= ua_icon('search', 18) ?><input name="q" type="search" placeholder="Search by name or email" value="<?= ua_escape($q) ?>" aria-label="Search users"></label>
        <label class="ua-filter-select"><span>Role</span><select name="role"><option value="">All roles</option><?php foreach (['student' => 'Student', 'owner' => 'Owner', 'admin' => 'Admin'] as $val => $label): ?><option value="<?= $val ?>" <?= $role === $val ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
        <label class="ua-filter-select"><span>Status</span><select name="status"><option value="">All statuses</option><option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option><option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option></select></label>
        <button class="ua-button ua-button-primary" type="submit"><?= ua_icon('filter', 16) ?> Filter</button>
        <a class="ua-button ua-button-outline" href="/public/admin/users.php">Reset</a>
    </form>
    <?php if ($dataError): ?><div class="ua-alert ua-alert-error" role="alert">Unable to load users. Check the server log.</div><?php endif; ?>
    <div class="ua-table-scroll"><table class="ua-table ua-table-spacious"><thead><tr><th>User</th><th>Role</th><th>Status</th><th>Account ID</th><th class="ua-actions-head">Manage</th></tr></thead><tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><div class="ua-user-cell"><span class="ua-table-avatar"><?= ua_escape(strtoupper(substr(trim((string) $user['full_name']) ?: '?', 0, 1))) ?></span><span><strong><?= ua_escape($user['full_name'] ?: 'Unnamed user') ?></strong><small><?= ua_escape($user['email']) ?></small></span></div></td>
                <td><?= ua_badge((string) $user['role']) ?></td>
                <td><?= ua_badge((string) ($user['status'] ?? 'unknown')) ?></td>
                <td class="ua-mono">#<?= (int) $user['user_id'] ?></td>
                <td>
                    <?php if (in_array($user['role'], ['student', 'owner'], true)): ?>
                        <form class="ua-inline-form" method="post" action="/public/admin/users.php" data-confirm="Update this account's access status?">
                            <input type="hidden" name="csrf_token" value="<?= ua_escape(ua_token()) ?>">
                            <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">
                            <select name="status" aria-label="Status for <?= ua_escape($user['full_name'] ?: $user['email']) ?>">
                                <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="suspended" <?= $user['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            </select>
                            <button class="ua-button ua-button-outline ua-button-small" type="submit">Save</button>
                        </form>
                    <?php else: ?><span class="ua-muted-small"><?= ua_icon('shield', 15) ?> Protected</span><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?><tr><td colspan="5" class="ua-empty-cell">No users match the selected filters.</td></tr><?php endif; ?>
    </tbody></table></div>
    <?php ua_pager($page, $total, $pageSize, ['q' => $q, 'role' => $role, 'status' => $status]); ?>
</section>
<?php ua_page_end(); ?>
