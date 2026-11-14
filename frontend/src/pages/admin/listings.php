<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../../backend/includes/admin_ui.php';

$pdo = ua_bootstrap();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ua_check_post();
    try {
        $id = ua_valid_positive_id($_POST['listing_id'] ?? null);
        if (($_POST['action'] ?? '') !== 'deactivate') {
            throw new InvalidArgumentException('Unsupported listing action.');
        }
        $stmt = $pdo->prepare("UPDATE `BoardingListing` SET availability = 'unavailable' WHERE listing_id = ? AND availability <> 'unavailable'");
        $stmt->execute([$id]);
        ua_flash($stmt->rowCount() ? 'Listing marked unavailable. Its data and tenancy history were preserved.' : 'The listing was already unavailable or no longer exists.', $stmt->rowCount() ? 'success' : 'error');
    } catch (InvalidArgumentException $e) {
        ua_flash($e->getMessage(), 'error');
    } catch (Throwable $e) {
        error_log('Admin listing moderation failed: ' . $e->getMessage());
        ua_flash('Unable to update the listing. Please try again.', 'error');
    }
    ua_redirect('listings.php');
}

$q = $_GET['q'] ?? '';
$q = is_string($q) ? trim($q) : '';
$q = substr($q, 0, 100);
$status = $_GET['status'] ?? '';
$status = is_string($status) ? trim($status) : '';
if (!in_array($status, ['', 'available', 'soon', 'unavailable'], true)) $status = '';
$page = ua_page_number($_GET['page'] ?? null);
$pageSize = 15;
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(b.title LIKE :q_title OR u.full_name LIKE :q_owner OR uni.name LIKE :q_uni)';
    $params[':q_title'] = '%' . $q . '%';
    $params[':q_owner'] = '%' . $q . '%';
    $params[':q_uni'] = '%' . $q . '%';
}
if ($status !== '') { $where[] = 'b.availability = :status'; $params[':status'] = $status; }
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$joins = ' FROM `BoardingListing` b LEFT JOIN `User` u ON b.owner_id = u.user_id LEFT JOIN `University` uni ON b.university_id = uni.university_id';

try {
    $countStmt = $pdo->prepare('SELECT COUNT(*)' . $joins . $whereSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $page = min($page, max(1, (int) ceil($total / $pageSize)));
    $offset = ($page - 1) * $pageSize;
    $stmt = $pdo->prepare(
        'SELECT b.listing_id, b.title, b.rent_amount, b.availability, b.capacity, b.cover_image_url, u.full_name AS owner_name, uni.name AS university_name'
        . $joins . $whereSql . ' ORDER BY b.listing_id DESC LIMIT ' . $pageSize . ' OFFSET ' . $offset
    );
    $stmt->execute($params);
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $dataError = false;
} catch (Throwable $e) {
    error_log('Admin listings list failed: ' . $e->getMessage());
    $listings = []; $total = 0; $dataError = true;
}

ua_page_start('listings', 'Manage Listings', 'Review properties, monitor availability and moderate listings.');
?>
<section class="ua-panel ua-table-panel">
    <div class="ua-panel-heading ua-panel-heading-wrap"><div><h2>All boarding places</h2><p><?= number_format($total) ?> listing(s) found</p></div><a class="ua-button ua-button-outline" href="/public/listings.php"><?= ua_icon('external', 16) ?> Public listing page</a></div>
    <form class="ua-filters" method="get" action="/public/admin/listings.php">
        <label class="ua-search-field"><?= ua_icon('search', 18) ?><input name="q" type="search" placeholder="Search listing, owner or university" value="<?= ua_escape($q) ?>" aria-label="Search listings"></label>
        <label class="ua-filter-select"><span>Availability</span><select name="status"><option value="">All listings</option><?php foreach (['available' => 'Available', 'soon' => 'Coming soon', 'unavailable' => 'Unavailable'] as $val => $label): ?><option value="<?= $val ?>" <?= $status === $val ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
        <button type="submit" class="ua-button ua-button-primary"><?= ua_icon('filter', 16) ?> Filter</button>
        <a class="ua-button ua-button-outline" href="/public/admin/listings.php">Reset</a>
    </form>
    <?php if ($dataError): ?><div class="ua-alert ua-alert-error" role="alert">Unable to load listings. Check the server log.</div><?php endif; ?>
    <div class="ua-table-scroll"><table class="ua-table ua-table-spacious"><thead><tr><th>Property</th><th>Owner</th><th>Monthly rent</th><th>Availability</th><th class="ua-actions-head">Action</th></tr></thead><tbody>
        <?php foreach ($listings as $listing): ?>
            <?php
            $img = trim((string) ($listing['cover_image_url'] ?? ''));
            $validImg = filter_var($img, FILTER_VALIDATE_URL)
                && strtolower((string) parse_url($img, PHP_URL_SCHEME)) === 'https'
                && in_array(strtolower((string) parse_url($img, PHP_URL_HOST)), ['res.cloudinary.com'], true);
            ?>
            <tr>
                <td><div class="ua-property-cell">
                    <?php if ($validImg): ?><img loading="lazy" src="<?= ua_escape($img) ?>" alt="" class="ua-listing-thumb"><?php else: ?><span class="ua-listing-thumb ua-thumb-placeholder"><?= ua_icon('house', 22) ?></span><?php endif; ?>
                    <span><strong><?= ua_escape($listing['title'] ?: 'Boarding #' . $listing['listing_id']) ?></strong><small><?= ua_escape($listing['university_name'] ?: 'University not specified') ?> · #<?= (int) $listing['listing_id'] ?></small></span>
                </div></td>
                <td><?= ua_escape($listing['owner_name'] ?: 'Owner not found') ?></td>
                <td class="ua-nowrap">Rs. <?= number_format((float) $listing['rent_amount']) ?></td>
                <td><?= ua_badge((string) $listing['availability']) ?></td>
                <td>
                    <div class="ua-row-actions">
                        <a class="ua-button ua-button-outline ua-button-small" href="/public/listing.php?id=<?= (int) $listing['listing_id'] ?>" aria-label="View listing <?= (int) $listing['listing_id'] ?>">View <?= ua_icon('external', 14) ?></a>
                        <?php if ($listing['availability'] !== 'unavailable'): ?>
                            <form method="post" action="/public/admin/listings.php" data-confirm="Make this listing unavailable? The owner and existing tenancies will remain intact.">
                                <input type="hidden" name="csrf_token" value="<?= ua_escape(ua_token()) ?>">
                                <input type="hidden" name="listing_id" value="<?= (int) $listing['listing_id'] ?>">
                                <input type="hidden" name="action" value="deactivate">
                                <button class="ua-button ua-button-danger-soft ua-button-small" type="submit">Mark unavailable</button>
                            </form>
                        <?php else: ?><span class="ua-muted-small">No action</span><?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$listings): ?><tr><td colspan="5" class="ua-empty-cell">No listings match your search.</td></tr><?php endif; ?>
    </tbody></table></div>
    <?php ua_pager($page, $total, $pageSize, ['q' => $q, 'status' => $status]); ?>
</section>
<?php ua_page_end(); ?>
