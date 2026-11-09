
<?php

$pageTitle = "Owner Dashboard | UniStay";

// Temporary demo data
$totalListings = 5;
$availableListings = 3;
$pendingInquiries = 4;
$totalTenants = 8;

$monthlyRentDue = 120000;
$monthlyRentPaid = 85000;

$recentInquiries = [
    [
        "name" => "Kasun Perera",
        "listing" => "Comfortable Student Boarding",
        "university" => "University of Moratuwa",
        "date" => "2026-11-07",
        "status" => "Pending"
    ],
    [
        "name" => "Nethmi Fernando",
        "listing" => "Girls Boarding Near Campus",
        "university" => "University of Moratuwa",
        "date" => "2026-11-06",
        "status" => "Pending"
    ],
    [
        "name" => "Amal Silva",
        "listing" => "Shared Student Room",
        "university" => "University of Colombo",
        "date" => "2026-11-05",
        "status" => "Accepted"
    ]
];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="../../src/styles/style.css">

</head>

<body>

<?php include __DIR__ . '/../../components/header.php'; ?>


<main class="owner-dashboard">

    <div class="container">

        <!-- Dashboard heading -->
        <section class="dashboard-heading">

            <div>
                <p class="dashboard-subtitle">OWNER PANEL</p>
                <h1>Dashboard</h1>

                <p class="dashboard-welcome">
                    Welcome back! Here's an overview of your boarding places.
                </p>
            </div>

            <a href="listings.php" class="btn btn-primary">
                Manage Listings
            </a>

        </section>


        <!-- Statistics -->
        <section class="dashboard-stats">

            <div class="dashboard-stat-card">

                <div class="stat-icon">⌂</div>

                <p>Total Listings</p>

                <h2><?php echo $totalListings; ?></h2>

                <span>Your registered properties</span>

            </div>


            <div class="dashboard-stat-card">

                <div class="stat-icon">✓</div>

                <p>Available Listings</p>

                <h2><?php echo $availableListings; ?></h2>

                <span>Currently available places</span>

            </div>


            <div class="dashboard-stat-card">

                <div class="stat-icon">✉</div>

                <p>Pending Inquiries</p>

                <h2><?php echo $pendingInquiries; ?></h2>

                <span>Waiting for your response</span>

            </div>


            <div class="dashboard-stat-card">

                <div class="stat-icon">♙</div>

                <p>Current Tenants</p>

                <h2><?php echo $totalTenants; ?></h2>

                <span>Registered tenants</span>

            </div>

        </section>


        <!-- Rent overview -->
        <section class="dashboard-rent-section">

            <div class="dashboard-section-heading">

                <div>
                    <p class="dashboard-subtitle">FINANCIAL OVERVIEW</p>
                    <h2>Monthly Rent Overview</h2>
                </div>

                <a href="rent.php" class="dashboard-text-link">
                    View Rent Records →
                </a>

            </div>


            <div class="dashboard-rent-card">

                <div class="rent-overview-item">

                    <span>Total Rent Due</span>

                    <h3>
                        Rs. <?php echo number_format($monthlyRentDue, 2); ?>
                    </h3>

                </div>


                <div class="rent-overview-item">

                    <span>Rent Collected</span>

                    <h3>
                        Rs. <?php echo number_format($monthlyRentPaid, 2); ?>
                    </h3>

                </div>


                <div class="rent-overview-item">

                    <span>Remaining Amount</span>

                    <h3>
                        Rs. <?php echo number_format($monthlyRentDue - $monthlyRentPaid, 2); ?>
                    </h3>

                </div>


                <div class="rent-progress-container">

                    <div class="rent-progress-label">
                        <span>Collection Progress</span>

                        <strong>
                            <?php
                            $collectionPercentage = $monthlyRentDue > 0
                                ? ($monthlyRentPaid / $monthlyRentDue) * 100
                                : 0;

                            echo round($collectionPercentage);
                            ?>%
                        </strong>
                    </div>

                    <div class="rent-progress-bar">

                        <div
                            class="rent-progress-fill"
                            style="width: <?php echo $collectionPercentage; ?>%;"
                        ></div>

                    </div>

                </div>

            </div>

        </section>


        <!-- Recent inquiries -->
        <section class="dashboard-inquiries-section">

            <div class="dashboard-section-heading">

                <div>
                    <p class="dashboard-subtitle">STUDENT REQUESTS</p>
                    <h2>Recent Inquiries</h2>
                </div>

                <a href="inquiries.php" class="dashboard-text-link">
                    View All Inquiries →
                </a>

            </div>


            <div class="dashboard-table-wrapper">

                <table class="dashboard-table">

                    <thead>

                        <tr>
                            <th>Student</th>
                            <th>Boarding Place</th>
                            <th>University</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($recentInquiries as $inquiry): ?>

                            <tr>

                                <td>
                                    <?php echo htmlspecialchars($inquiry["name"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($inquiry["listing"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($inquiry["university"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($inquiry["date"]); ?>
                                </td>

                                <td>

                                    <span class="inquiry-status
                                        <?php echo strtolower($inquiry["status"]); ?>">

                                        <?php echo htmlspecialchars($inquiry["status"]); ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- Quick actions -->
        <section class="dashboard-actions-section">

            <div class="dashboard-section-heading">

                <div>
                    <p class="dashboard-subtitle">QUICK ACCESS</p>
                    <h2>Manage Your Account</h2>
                </div>

            </div>


            <div class="dashboard-actions-grid">

                <a href="listing_form.php" class="dashboard-action-card">

                    <span class="action-icon">＋</span>

                    <h3>Add New Listing</h3>

                    <p>Register another boarding property.</p>

                    <span class="action-arrow">→</span>

                </a>


                <a href="listings.php" class="dashboard-action-card">

                    <span class="action-icon">⌂</span>

                    <h3>My Listings</h3>

                    <p>View and manage your properties.</p>

                    <span class="action-arrow">→</span>

                </a>


                <a href="inquiries.php" class="dashboard-action-card">

                    <span class="action-icon">✉</span>

                    <h3>Student Inquiries</h3>

                    <p>Review and respond to student requests.</p>

                    <span class="action-arrow">→</span>

                </a>


                <a href="tenants.php" class="dashboard-action-card">

                    <span class="action-icon">♙</span>

                    <h3>My Tenants</h3>

                    <p>View your current boarding tenants.</p>

                    <span class="action-arrow">→</span>

                </a>


            </div>

        </section>

    </div>

</main>


<?php include __DIR__ . '/../../components/footer.php'; ?>