
<?php
$rentRecords = [
    [
        'id' => 1,
        'tenant' => 'Nethmi Fernando',
        'listing' => 'Modern Girls Boarding',
        'billing_month' => '2026-11',
        'amount_due' => 18000,
        'amount_paid' => 18000,
        'due_date' => '2026-11-05',
        'payment_date' => '2026-11-03',
        'method' => 'bank_transfer'
    ],
    [
        'id' => 2,
        'tenant' => 'Kavindu Perera',
        'listing' => 'Comfortable Student Boarding',
        'billing_month' => '2026-11',
        'amount_due' => 15000,
        'amount_paid' => 5000,
        'due_date' => '2026-11-10',
        'payment_date' => '2026-11-06',
        'method' => 'cash'
    ],
    [
        'id' => 3,
        'tenant' => 'Tharushi Jayawardena',
        'listing' => 'Budget Student Rooms',
        'billing_month' => '2026-11',
        'amount_due' => 12000,
        'amount_paid' => 0,
        'due_date' => '2026-11-05',
        'payment_date' => null,
        'method' => null
    ],
    [
        'id' => 4,
        'tenant' => 'Sahan Silva',
        'listing' => 'Budget Student Rooms',
        'billing_month' => '2026-10',
        'amount_due' => 12000,
        'amount_paid' => 0,
        'due_date' => '2026-10-05',
        'payment_date' => null,
        'method' => null
    ]
];

$tenants = [
    'Nethmi Fernando',
    'Kavindu Perera',
    'Tharushi Jayawardena'
];

$successMessage = '';
$errorMessage = '';

$today = date('Y-m-d');

// Calculate the status of a rent record.
function getRentStatus($record, $today)
{
    if ($record['amount_paid'] >= $record['amount_due']) {
        return 'paid';
    }

    if ($record['amount_paid'] > 0) {
        return 'partial';
    }

    if ($record['due_date'] < $today) {
        return 'overdue';
    }

    return 'pending';
}

// Handle the rent form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenant = trim($_POST['tenant'] ?? '');
    $billingMonth = $_POST['billing_month'] ?? '';
    $amountDue = filter_input(INPUT_POST, 'amount_due', FILTER_VALIDATE_FLOAT);
    $amountPaid = filter_input(INPUT_POST, 'amount_paid', FILTER_VALIDATE_FLOAT);
    $dueDate = $_POST['due_date'] ?? '';
    $paymentDate = $_POST['payment_date'] ?? '';
    $method = $_POST['method'] ?? '';

    $validMethods = ['cash', 'bank_transfer'];

    $validMonth = DateTime::createFromFormat('!Y-m', $billingMonth);
    $validDueDate = DateTime::createFromFormat('!Y-m-d', $dueDate);

    $validPaymentDate = true;

    if ($paymentDate !== '') {
        $validPaymentDate = DateTime::createFromFormat('!Y-m-d', $paymentDate) !== false;
    }

    if (
        !in_array($tenant, $tenants, true) ||
        !$validMonth ||
        $validMonth->format('Y-m') !== $billingMonth ||
        !$validDueDate ||
        $validDueDate->format('Y-m-d') !== $dueDate ||
        !$validPaymentDate ||
        $amountDue === false ||
        $amountDue <= 0 ||
        $amountPaid === false ||
        $amountPaid < 0 ||
        $amountPaid > $amountDue
    ) {
        $errorMessage = 'Please enter valid rent details. The amount paid cannot exceed the amount due.';
    } elseif ($amountPaid > 0 && (
        $paymentDate === '' ||
        !in_array($method, $validMethods, true)
    )) {
        $errorMessage = 'Please provide the payment date and method when recording a payment.';
    } elseif ($paymentDate !== '' && $paymentDate > $today) {
        $errorMessage = 'Payment date cannot be in the future.';
    } else {
        $successMessage = 'Rent record submitted successfully (demo only).';
    }
}

// Calculate dashboard totals.
$totalDue = 0;
$totalPaid = 0;
$totalOutstanding = 0;

foreach ($rentRecords as $record) {
    $totalDue += $record['amount_due'];
    $totalPaid += $record['amount_paid'];
    $totalOutstanding += $record['amount_due'] - $record['amount_paid'];
}

// Apply filters.
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$monthFilter = $_GET['month'] ?? '';

$filteredRecords = [];

foreach ($rentRecords as $record) {
    $status = getRentStatus($record, $today);

    $matchesSearch = (
        $search === '' ||
        stripos($record['tenant'], $search) !== false ||
        stripos($record['listing'], $search) !== false
    );

    $matchesStatus = (
        $statusFilter === 'all' ||
        $statusFilter === $status
    );

    $matchesMonth = (
        $monthFilter === '' ||
        $monthFilter === $record['billing_month']
    );

    if ($matchesSearch && $matchesStatus && $matchesMonth) {
        $filteredRecords[] = $record;
    }
}

include __DIR__ . '/../../components/header.php';
?>

<head>
    <link rel="stylesheet" href="../../src/styles/style.css">
</head>

<main class="owner-rent-page">

    <section class="owner-page-heading">
        <div>
            <span class="owner-page-eyebrow">OWNER PORTAL</span>
            <h1>Monthly Rent Management</h1>
            <p>Record monthly rent payments and monitor outstanding balances.</p>
        </div>
    </section>

    <?php if ($successMessage): ?>
        <div class="owner-rent-alert owner-rent-alert-success">
            <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="owner-rent-alert owner-rent-alert-error">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <!-- Rent summary -->
    <section class="owner-rent-stats">

        <div class="owner-rent-stat">
            <span>Total Rent Due</span>
            <strong>Rs. <?= number_format($totalDue, 2) ?></strong>
        </div>

        <div class="owner-rent-stat">
            <span>Total Collected</span>
            <strong>Rs. <?= number_format($totalPaid, 2) ?></strong>
        </div>

        <div class="owner-rent-stat">
            <span>Outstanding Balance</span>
            <strong>Rs. <?= number_format($totalOutstanding, 2) ?></strong>
        </div>

    </section>

    <!-- Record rent form -->
    <section class="owner-rent-form-section">

        <div class="owner-section-heading">
            <div>
                <h2>Record Rent Payment</h2>
                <p>Enter the rent details for one tenant and billing month.</p>
            </div>
        </div>

        <form method="POST" class="owner-rent-form">

            <div class="owner-rent-form-grid">

                <div class="owner-rent-field">
                    <label for="tenant">Select Tenant</label>
                    <select name="tenant" id="tenant" required>
                        <option value="">Choose a tenant</option>

                        <?php foreach ($tenants as $tenant): ?>
                            <option
                                value="<?= htmlspecialchars($tenant) ?>"
                                <?= (($_POST['tenant'] ?? '') === $tenant) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($tenant) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="owner-rent-field">
                    <label for="billing_month">Billing Month</label>
                    <input
                        type="month"
                        name="billing_month"
                        id="billing_month"
                        value="<?= htmlspecialchars($_POST['billing_month'] ?? date('Y-m')) ?>"
                        required
                    >
                </div>

                <div class="owner-rent-field">
                    <label for="amount_due">Amount Due (Rs.)</label>
                    <input
                        type="number"
                        name="amount_due"
                        id="amount_due"
                        min="1"
                        step="0.01"
                        value="<?= htmlspecialchars($_POST['amount_due'] ?? '') ?>"
                        placeholder="e.g. 15000"
                        required
                    >
                </div>

                <div class="owner-rent-field">
                    <label for="amount_paid">Amount Paid (Rs.)</label>
                    <input
                        type="number"
                        name="amount_paid"
                        id="amount_paid"
                        min="0"
                        step="0.01"
                        value="<?= htmlspecialchars($_POST['amount_paid'] ?? '0') ?>"
                        placeholder="e.g. 15000"
                        required
                    >
                </div>

                <div class="owner-rent-field">
                    <label for="due_date">Due Date</label>
                    <input
                        type="date"
                        name="due_date"
                        id="due_date"
                        value="<?= htmlspecialchars($_POST['due_date'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="owner-rent-field">
                    <label for="payment_date">Payment Date</label>
                    <input
                        type="date"
                        name="payment_date"
                        id="payment_date"
                        value="<?= htmlspecialchars($_POST['payment_date'] ?? '') ?>"
                    >
                </div>

                <div class="owner-rent-field">
                    <label for="method">Payment Method</label>
                    <select name="method" id="method">
                        <option value="">Select method</option>
                        <option value="cash" <?= (($_POST['method'] ?? '') === 'cash') ? 'selected' : '' ?>>
                            Cash
                        </option>
                        <option value="bank_transfer" <?= (($_POST['method'] ?? '') === 'bank_transfer') ? 'selected' : '' ?>>
                            Bank Transfer
                        </option>
                    </select>
                </div>

            </div>

            <p class="owner-rent-form-note">
                If no payment has been made, enter 0 as the amount paid and leave
                the payment date and method empty.
            </p>

            <button type="submit" class="owner-rent-submit">
                Save Rent Record
            </button>

        </form>

    </section>

    <!-- Rent records -->
    <section class="owner-rent-records-section">

        <div class="owner-section-heading">
            <div>
                <h2>Rent Records</h2>
                <p>Review monthly rent records and payment statuses.</p>
            </div>
        </div>

        <form method="GET" class="owner-rent-filters">

            <input
                type="text"
                name="search"
                placeholder="Search tenant or boarding..."
                value="<?= htmlspecialchars($search) ?>"
            >

            <select name="status">
                <option value="all">All Statuses</option>
                <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Paid</option>
                <option value="partial" <?= $statusFilter === 'partial' ? 'selected' : '' ?>>Partial</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="overdue" <?= $statusFilter === 'overdue' ? 'selected' : '' ?>>Overdue</option>
            </select>

            <input
                type="month"
                name="month"
                value="<?= htmlspecialchars($monthFilter) ?>"
            >

            <button type="submit">Filter</button>

            <a href="rent.php" class="owner-rent-reset">Reset</a>

        </form>

        <?php if (count($filteredRecords) > 0): ?>

            <div class="owner-rent-record-list">

                <?php foreach ($filteredRecords as $record): ?>

                    <?php $status = getRentStatus($record, $today); ?>

                    <article class="owner-rent-record-card">

                        <div class="owner-rent-record-header">

                            <div>
                                <span class="owner-rent-record-id">
                                    RENT RECORD #<?= $record['id'] ?>
                                </span>

                                <h3><?= htmlspecialchars($record['tenant']) ?></h3>

                                <p><?= htmlspecialchars($record['listing']) ?></p>
                            </div>

                            <span class="owner-rent-status status-<?= $status ?>">
                                <?= ucfirst($status) ?>
                            </span>

                        </div>

                        <div class="owner-rent-record-details">

                            <div>
                                <span>Billing Month</span>
                                <strong>
                                    <?= date('F Y', strtotime($record['billing_month'] . '-01')) ?>
                                </strong>
                            </div>

                            <div>
                                <span>Amount Due</span>
                                <strong>Rs. <?= number_format($record['amount_due'], 2) ?></strong>
                            </div>

                            <div>
                                <span>Amount Paid</span>
                                <strong>Rs. <?= number_format($record['amount_paid'], 2) ?></strong>
                            </div>

                            <div>
                                <span>Outstanding</span>
                                <strong>
                                    Rs. <?= number_format(
                                        $record['amount_due'] - $record['amount_paid'],
                                        2
                                    ) ?>
                                </strong>
                            </div>

                            <div>
                                <span>Due Date</span>
                                <strong><?= htmlspecialchars($record['due_date']) ?></strong>
                            </div>

                            <div>
                                <span>Payment Date</span>
                                <strong>
                                    <?= $record['payment_date']
                                        ? htmlspecialchars($record['payment_date'])
                                        : 'Not recorded' ?>
                                </strong>
                            </div>

                            <div>
                                <span>Payment Method</span>
                                <strong>
                                    <?php
                                    if ($record['method'] === 'bank_transfer') {
                                        echo 'Bank Transfer';
                                    } elseif ($record['method'] === 'cash') {
                                        echo 'Cash';
                                    } else {
                                        echo 'Not recorded';
                                    }
                                    ?>
                                </strong>
                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="owner-rent-empty">
                <h3>No Rent Records Found</h3>
                <p>Try changing your search or filters.</p>
            </div>

        <?php endif; ?>

    </section>

</main>

<?php include __DIR__ . '/../../components/footer.php'; ?>
