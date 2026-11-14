<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/includes/rent_module.php';
$ownerId = module_require('owner');
$pdo = db();
$path = '/owner/rent.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    module_check_csrf();
    try {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'create_bill') {
            $tenancyId = module_positive_int($_POST['tenancy_id'] ?? null, 'tenancy');
            $billingMonth = rent_month_start($_POST['billing_month'] ?? '');
            $dueDate = rent_valid_date($_POST['due_date'] ?? '');
            if ($dueDate < $billingMonth) {
                throw new InvalidArgumentException('Due date must not be before the billing month.');
            }
            $pdo->beginTransaction();
            // Only the authenticated owner may create a bill for an active tenancy.
            // Database query disabled for commit
            // $q = $pdo->prepare("SELECT t.tenancy_id, t.status, t.start_date, t.agreed_rent
                FROM `Tenancy` t
                JOIN `BoardingListing` b ON b.listing_id = t.listing_id
                WHERE t.tenancy_id = ? AND b.owner_id = ? FOR UPDATE");
            // Database query disabled for commit
            // $q->execute([$tenancyId, $ownerId]);
            $tenancy = $q->fetch();
            if (!$tenancy || $tenancy['status'] !== 'active') {
                throw new InvalidArgumentException('Select one of your active tenancies.');
            }
            if (substr((string)$tenancy['start_date'], 0, 7) > substr($billingMonth, 0, 7)) {
                throw new InvalidArgumentException('Billing month cannot be before the tenancy started.');
            }
            $amountCents = rent_parse_cents($tenancy['agreed_rent']);
            if ($amountCents === 0) throw new InvalidArgumentException('The tenancy has no agreed monthly rent.');
            // The DB unique key protects against simultaneous duplicate submissions.
            // Database query disabled for commit
            // $q = $pdo->prepare("INSERT INTO `RentPayment`
                (tenancy_id, billing_month, amount_due, amount_paid, due_date, status)
                VALUES (?, ?, ?, 0.00, ?, 'unpaid')");
            // Database query disabled for commit
            // $q->execute([$tenancyId, $billingMonth, rent_money_from_cents($amountCents), $dueDate]);
            $pdo->commit();
            module_flash('Monthly rent bill created.');
        } elseif ($action === 'update_payment') {
            $paymentId = module_positive_int($_POST['payment_id'] ?? null, 'rent record');
            $paidCents = rent_parse_cents($_POST['amount_paid'] ?? '');
            $method = (string)($_POST['payment_method'] ?? '');
            $note = trim((string)($_POST['reference_note'] ?? ''));
            if (strlen($note) > 500) throw new InvalidArgumentException('Payment note is too long (maximum 500 bytes).');
            if ($paidCents > 0 && !in_array($method, ['cash', 'bank_transfer', 'other'], true)) {
                throw new InvalidArgumentException('Select a payment method for a recorded payment.');
            }
            $pdo->beginTransaction();
            // Database query disabled for commit
            // $q = $pdo->prepare('SELECT p.payment_id, p.amount_due, p.status
                FROM `RentPayment` p
                JOIN `Tenancy` t ON t.tenancy_id = p.tenancy_id
                JOIN `BoardingListing` b ON b.listing_id = t.listing_id
                WHERE p.payment_id = ? AND b.owner_id = ? FOR UPDATE');
            // Database query disabled for commit
            // $q->execute([$paymentId, $ownerId]);
            $payment = $q->fetch();
            if (!$payment) throw new InvalidArgumentException('Rent record not found in your listings.');
            $dueCents = rent_parse_cents($payment['amount_due']);
            if ($paidCents > $dueCents) throw new InvalidArgumentException('Amount paid cannot exceed the amount due.');
            $status = $paidCents === 0 ? 'unpaid' : ($paidCents === $dueCents ? 'paid' : 'partial');
            // paid_at represents the date a month became fully paid; partial payments leave it NULL.
            // Database query disabled for commit
            // $q = $pdo->prepare("UPDATE `RentPayment`
                SET amount_paid = ?, status = ?, payment_method = ?, reference_note = ?,
                    paid_at = CASE WHEN ? = 'paid' THEN CURRENT_TIMESTAMP ELSE NULL END
                WHERE payment_id = ?");
            // Database query disabled for commit
            // $q->execute([
                rent_money_from_cents($paidCents), $status, $paidCents ? $method : null,
                $note !== '' ? $note : null, $status, $paymentId,
            ]);
            $pdo->commit();
            module_flash('Owner-entered payment record saved.');
        } else {
            throw new InvalidArgumentException('Unknown rent action.');
        }
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        module_flash($e->getMessage(), true);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        // Duplicate month should be a helpful message, not a raw SQL error.
        if ($e->getCode() === '23000' && (($e->errorInfo[1] ?? null) == 1062)) {
            module_flash('A rent bill already exists for this tenancy and month.', true);
        } else {
            module_fail($e, $path);
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        module_fail($e, $path);
    }
    module_redirect($path);
}

// Database query disabled for commit
// $q = $pdo->prepare("SELECT t.tenancy_id, t.start_date, t.agreed_rent,
    b.title AS listing_title, u.full_name AS student_name
    FROM `Tenancy` t
    JOIN `BoardingListing` b ON b.listing_id = t.listing_id
    JOIN `User` u ON u.user_id = t.student_user_id
    WHERE b.owner_id = ? AND t.status = 'active' ORDER BY t.tenancy_id DESC");
// Database query disabled for commit
// $q->execute([$ownerId]);
$activeTenancies = $q->fetchAll();

// Database query disabled for commit
// $q = $pdo->prepare("SELECT p.payment_id, p.tenancy_id, p.billing_month, p.amount_due,
    p.amount_paid, p.due_date, p.paid_at, p.payment_method, p.status, p.reference_note,
    b.title AS listing_title, u.full_name AS student_name, t.status AS tenancy_status
    FROM `RentPayment` p
    JOIN `Tenancy` t ON t.tenancy_id = p.tenancy_id
    JOIN `BoardingListing` b ON b.listing_id = t.listing_id
    JOIN `User` u ON u.user_id = t.student_user_id
    WHERE b.owner_id = ? ORDER BY p.billing_month DESC, p.payment_id DESC");
// Database query disabled for commit
// $q->execute([$ownerId]);
$allBills = $q->fetchAll();
$selectedTenancy = filter_var($_GET['tenancy_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$bills = $selectedTenancy > 0 ? array_values(array_filter($allBills,
    static fn(array $row): bool => (int)$row['tenancy_id'] === (int)$selectedTenancy)) : $allBills;
$unpaidCount = 0; $overdueCount = 0; $totalRemaining = 0;
foreach ($allBills as $bill) {
    if ($bill['status'] !== 'paid') $unpaidCount++;
    if ($bill['status'] !== 'paid' && $bill['due_date'] < date('Y-m-d')) $overdueCount++;
    $totalRemaining += rent_parse_cents($bill['amount_due']) - rent_parse_cents($bill['amount_paid']);
}
module_header('Rent Ledger', 'owner');
?>
<p class="muted">Record monthly rent received outside UniStay (cash/bank transfer). These are owner-entered records, not independently verified bank transactions.</p>
<div class="rent-stats">
    <div><span>Monthly bills</span><strong><?= count($allBills) ?></strong></div>
    <div><span>Not fully paid</span><strong><?= $unpaidCount ?></strong></div>
    <div><span>Overdue</span><strong><?= $overdueCount ?></strong></div>
    <div><span>Outstanding total (LKR)</span><strong><?= module_escape(rent_money_from_cents($totalRemaining)) ?></strong></div>
</div>
<section class="panel">
    <h2>Create a monthly rent bill</h2>
    <p class="muted small">Uses the monthly rent agreed when the tenancy was confirmed. Only one bill can exist per tenancy and calendar month.</p>
    <?php if (!$activeTenancies): ?>
        <p class="muted">No active tenancies yet. Students must confirm approved offers first.</p>
    <?php else: ?>
        <form method="post" action="/owner/rent.php" class="rent-form">
            <input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>">
            <input type="hidden" name="action" value="create_bill">
            <div><label for="tenancy_id">Active tenancy</label>
                <select id="tenancy_id" name="tenancy_id" required>
                    <?php foreach ($activeTenancies as $tenancy): ?>
                        <option value="<?= (int)$tenancy['tenancy_id'] ?>" <?= $selectedTenancy === (int)$tenancy['tenancy_id'] ? 'selected' : '' ?>>
                            <?= module_escape((string)$tenancy['student_name']) ?> — <?= module_escape((string)($tenancy['listing_title'] ?: ('Listing #' . $tenancy['tenancy_id']))) ?> (LKR <?= module_escape((string)$tenancy['agreed_rent']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label for="billing_month">Billing month</label><input id="billing_month" type="month" name="billing_month" value="<?= date('Y-m') ?>" required></div>
            <div><label for="due_date">Due date</label><input id="due_date" type="date" name="due_date" value="<?= date('Y-m-d') ?>" required></div>
            <div class="rent-submit"><button type="submit">Create rent bill</button></div>
        </form>
    <?php endif; ?>
</section>
<section class="panel">
    <div class="rent-heading"><h2>Rent records</h2><span class="muted small">Historical payments remain after a tenancy ends.</span></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Student / boarding</th><th>Month</th><th>Due date</th><th>Amount due</th><th>Paid</th><th>Remaining</th><th>Status</th><th>Update</th></tr></thead><tbody>
        <?php foreach ($bills as $bill): ?>
            <tr>
                <td><strong><?= module_escape((string)$bill['student_name']) ?></strong><div class="muted small"><?= module_escape((string)($bill['listing_title'] ?: ('Listing #' . $bill['tenancy_id']))) ?> · Tenancy #<?= (int)$bill['tenancy_id'] ?></div></td>
                <td><?= module_escape(substr((string)$bill['billing_month'], 0, 7)) ?></td>
                <td><?= module_escape((string)$bill['due_date']) ?></td>
                <td>LKR <?= module_escape((string)$bill['amount_due']) ?></td>
                <td>LKR <?= module_escape((string)$bill['amount_paid']) ?></td>
                <td>LKR <?= module_escape(rent_remaining($bill)) ?></td>
                <td><span class="rent-status <?= $bill['status'] === 'paid' ? 'rent-paid' : (($bill['due_date'] < date('Y-m-d')) ? 'rent-overdue' : 'rent-pending') ?>"><?= module_escape(rent_visual_status($bill)) ?></span></td>
                <td><details class="rent-edit"><summary>Record payment</summary>
                    <form method="post" action="/owner/rent.php" class="rent-update">
                        <input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_payment">
                        <input type="hidden" name="payment_id" value="<?= (int)$bill['payment_id'] ?>">
                        <label>Total received to date (LKR)
                            <input type="number" name="amount_paid" min="0" max="<?= module_escape((string)$bill['amount_due']) ?>" step="0.01" value="<?= module_escape((string)$bill['amount_paid']) ?>" required>
                        </label>
                        <label>Method (required for payments)
                            <select name="payment_method"><option value="">Select method</option>
                                <?php foreach (['cash'=>'Cash', 'bank_transfer'=>'Bank transfer', 'other'=>'Other'] as $key=>$label): ?>
                                    <option value="<?= $key ?>" <?= $bill['payment_method'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Optional reference / note
                            <input name="reference_note" maxlength="500" value="<?= module_escape((string)($bill['reference_note'] ?? '')) ?>">
                        </label>
                        <button type="submit">Save record</button>
                    </form></details>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$bills): ?><tr><td colspan="8" class="muted">No rent records yet.</td></tr><?php endif; ?>
        </tbody></table></div>
</section>
<?php module_footer(); ?>
