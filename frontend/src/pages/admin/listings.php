<?php

$pageTitle = "Manage Listings | UniStay Admin";

require_once __DIR__ . '/../../../../config/db_connect.php';
require_once __DIR__ . '/../../../../backend/auth.php';

require_admin();

$pdo = db();

// Handle forced deactivation of a listing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'deactivate' && !empty($_POST['listing_id'])) {
        
        // Soft-delete to preserve tenancy history
        $stmt = $pdo->prepare("UPDATE boarding_listings SET availability_status = 'unavailable' WHERE listing_id = ?");
        $stmt->execute([$_POST['listing_id']]);

    }

}

// Fetch all listings with owner details
$query = "
    SELECT l.listing_id, l.title, l.rent_amount, l.availability_status, u.full_name as owner_name 
    FROM boarding_listings l 
    JOIN `User` u ON l.owner_id = u.user_id
    ORDER BY l.created_at DESC
";

$listings = $pdo->query($query)->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="../../src/styles/style.css">
    <link rel="stylesheet" href="listings.css">

</head>

<body>

<?php include __DIR__ . '/../../components/header.php'; ?>


<main class="admin-page-layout">

    <div class="container">

        <!-- Page Heading -->
        <section class="dashboard-heading">

            <div>
                <p class="dashboard-subtitle">UNISTAY MANAGEMENT</p>
                <h1>Manage Listings</h1>

                <p class="dashboard-welcome">
                    Monitor and moderate all property listings across the platform.
                </p>
            </div>

        </section>


        <!-- Listings Table Panel -->
        <section class="admin-panel">

            <div class="admin-table-wrapper">

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Owner Name</th>
                            <th>Rent Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($listings as $listing): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($listing['title']); ?></strong>
                                </td>
                                
                                <td>
                                    <?php echo htmlspecialchars($listing['owner_name']); ?>
                                </td>
                                
                                <td>
                                    Rs. <?php echo htmlspecialchars(number_format($listing['rent_amount'], 2)); ?>
                                </td>
                                
                                <td>
                                    <?php 
                                        $statusClass = ($listing['availability_status'] === 'available') ? 'status-available' : 'status-unavailable';
                                    ?>
                                    <span class="status-tag <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($listing['availability_status']); ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="listing_id" value="<?php echo $listing['listing_id']; ?>">
                                        <button type="submit" name="action" value="deactivate" class="btn btn-outline" style="color: #d93025; border-color: #d93025;">
                                            Force Unavailable
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>

            </div>

        </section>

    </div>

</main>


<?php include __DIR__ . '/../../components/footer.php'; ?>

</body>
</html>
