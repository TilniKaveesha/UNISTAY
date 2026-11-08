
<?php
$tenants = [
    [
        'id' => 1,
        'name' => 'Nethmi Fernando',
        'email' => 'nethmi@example.com',
        'phone' => '071 234 5678',
        'university' => 'University of Moratuwa',
        'listing' => 'Modern Girls Boarding',
        'start_date' => '2026-10-01',
        'rent' => 18000,
        'status' => 'active'
    ],
    [
        'id' => 2,
        'name' => 'Kavindu Perera',
        'email' => 'kavindu@example.com',
        'phone' => '077 123 4567',
        'university' => 'University of Moratuwa',
        'listing' => 'Comfortable Student Boarding',
        'start_date' => '2026-09-15',
        'rent' => 15000,
        'status' => 'active'
    ],
    [
        'id' => 3,
        'name' => 'Sahan Silva',
        'email' => 'sahan@example.com',
        'phone' => '076 345 6789',
        'university' => 'University of Colombo',
        'listing' => 'Budget Student Rooms',
        'start_date' => '2026-08-01',
        'rent' => 12000,
        'status' => 'ended'
    ],
    [
        'id' => 4,
        'name' => 'Tharushi Jayawardena',
        'email' => 'tharushi@example.com',
        'phone' => '075 456 7890',
        'university' => 'University of Moratuwa',
        'listing' => 'Budget Student Rooms',
        'start_date' => '2026-11-01',
        'rent' => 12000,
        'status' => 'active'
    ]
];

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';

$filteredTenants = [];

foreach ($tenants as $tenant) {
    $matchesSearch = true;
    $matchesStatus = true;

    if ($search !== '') {
        $searchText = strtolower($search);

        $matchesSearch =
            strpos(strtolower($tenant['name']), $searchText) !== false ||
            strpos(strtolower($tenant['email']), $searchText) !== false ||
            strpos(strtolower($tenant['listing']), $searchText) !== false ||
            strpos(strtolower($tenant['university']), $searchText) !== false;
    }

    if ($statusFilter !== 'all') {
        $matchesStatus = $tenant['status'] === $statusFilter;
    }

    if ($matchesSearch && $matchesStatus) {
        $filteredTenants[] = $tenant;
    }
}

$totalTenants = count($tenants);
$activeTenants = 0;
$endedTenants = 0;

foreach ($tenants as $tenant) {
    if ($tenant['status'] === 'active') {
        $activeTenants++;
    } else {
        $endedTenants++;
    }
}

include __DIR__ . '/../../components/header.php';
?>

<link rel="stylesheet" href="/UNISTAY/frontend/src/styles/style.css">

<main class="owner-tenants-page">

    <section class="owner-page-heading">
        <div>
            <span class="owner-page-eyebrow">OWNER PORTAL</span>
            <h1>My Tenants</h1>
            <p>View and manage students currently staying in your boarding places.</p>
        </div>
    </section>

    <section class="owner-tenant-stats">

        <div class="owner-tenant-stat">
            <span>Total Tenants</span>
            <strong><?= $totalTenants ?></strong>
        </div>

        <div class="owner-tenant-stat">
            <span>Active Tenants</span>
            <strong><?= $activeTenants ?></strong>
        </div>

        <div class="owner-tenant-stat">
            <span>Ended Tenancies</span>
            <strong><?= $endedTenants ?></strong>
        </div>

    </section>

    <section class="owner-tenant-section">

        <div class="owner-section-heading">
            <div>
                <h2>Tenant Records</h2>
                <p>Students registered under your boarding listings.</p>
            </div>
        </div>

        <form method="GET" class="owner-tenant-filters">

            <input
                type="text"
                name="search"
                placeholder="Search by name, email, listing or university..."
                value="<?= htmlspecialchars($search) ?>"
            >

            <select name="status">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>
                    All Tenants
                </option>

                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>
                    Active
                </option>

                <option value="ended" <?= $statusFilter === 'ended' ? 'selected' : '' ?>>
                    Ended
                </option>
            </select>

            <button type="submit">Search</button>

            <a href="tenants.php" class="owner-tenant-reset">Reset</a>

        </form>

        <?php if (count($filteredTenants) > 0): ?>

            <div class="owner-tenant-list">

                <?php foreach ($filteredTenants as $tenant): ?>

                    <article class="owner-tenant-card">

                        <div class="owner-tenant-card-header">

                            <div>
                                <span class="owner-tenant-id">
                                    TENANT #<?= $tenant['id'] ?>
                                </span>

                                <h3><?= htmlspecialchars($tenant['name']) ?></h3>

                                <p>
                                    <?= htmlspecialchars($tenant['university']) ?>
                                </p>
                            </div>

                            <span class="owner-tenant-status status-<?= $tenant['status'] ?>">
                                <?= ucfirst($tenant['status']) ?>
                            </span>

                        </div>

                        <div class="owner-tenant-details">

                            <div>
                                <span>Email Address</span>
                                <strong><?= htmlspecialchars($tenant['email']) ?></strong>
                            </div>

                            <div>
                                <span>Phone Number</span>
                                <strong><?= htmlspecialchars($tenant['phone']) ?></strong>
                            </div>

                            <div>
                                <span>Boarding Listing</span>
                                <strong><?= htmlspecialchars($tenant['listing']) ?></strong>
                            </div>

                            <div>
                                <span>Tenancy Start Date</span>
                                <strong><?= htmlspecialchars($tenant['start_date']) ?></strong>
                            </div>

                            <div>
                                <span>Monthly Rent</span>
                                <strong>
                                    Rs. <?= number_format($tenant['rent'], 2) ?>
                                </strong>
                            </div>

                        </div>

                        <?php if ($tenant['status'] === 'active'): ?>
                            <div class="owner-tenant-card-footer">
                                <span>Currently staying</span>
                                <a href="rent.php">View Rent Records</a>
                            </div>
                        <?php else: ?>
                            <div class="owner-tenant-card-footer">
                                <span>Tenancy has ended</span>
                            </div>
                        <?php endif; ?>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="owner-tenant-empty">
                <h3>No Tenants Found</h3>
                <p>Try changing your search or status filter.</p>
            </div>

        <?php endif; ?>

    </section>

</main>

<?php include __DIR__ . '/../../components/footer.php'; ?>
