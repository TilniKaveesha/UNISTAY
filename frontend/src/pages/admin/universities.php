<?php

$pageTitle = "Manage Universities | UniStay Admin";

require_once __DIR__ . '/../../../../config/db_connect.php';
require_once __DIR__ . '/../../../../backend/auth.php';

require_admin();

$pdo = db();

// Handle adding a new university to the directory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_university'])) {

    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (!empty($name) && !empty($city) && !empty($address)) {
        
        $stmt = $pdo->prepare("INSERT INTO universities (name, city, address) VALUES (?, ?, ?)");
        $stmt->execute([$name, $city, $address]);

    }

}

// Fetch current directory
$universities = $pdo->query("SELECT * FROM universities ORDER BY name ASC")->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="../../src/styles/style.css">
    <link rel="stylesheet" href="universities.css">

</head>

<body>

<?php include __DIR__ . '/../../components/header.php'; ?>


<main class="admin-page-layout">

    <div class="container">

        <!-- Page Heading -->
        <section class="dashboard-heading">

            <div>
                <p class="dashboard-subtitle">UNISTAY MANAGEMENT</p>
                <h1>Manage Universities</h1>

                <p class="dashboard-welcome">
                    Keep the supported university directory up to date.
                </p>
            </div>

        </section>


        <!-- Add University Form -->
        <section class="admin-panel" style="margin-bottom: 30px; background-color: #fafcff;">

            <div class="admin-panel-header">
                <h2>Add New University</h2>
            </div>

            <form method="POST" class="admin-form">

                <div class="form-group">
                    <label for="name">University Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. University of Moratuwa" required>
                </div>

                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" class="form-control" placeholder="e.g. Moratuwa" required>
                </div>

                <div class="form-group">
                    <label for="address">Full Address</label>
                    <input type="text" id="address" name="address" class="form-control" placeholder="e.g. Bandaranayake Mawatha" required>
                </div>

                <div class="form-group">
                    <button type="submit" name="add_university" class="btn btn-primary" style="padding: 11px 20px;">
                        Add to Directory
                    </button>
                </div>

            </form>

        </section>


        <!-- University Directory Table -->
        <section class="admin-panel">

            <div class="admin-table-wrapper">

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th>University Name</th>
                            <th>City</th>
                            <th>Full Address</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($universities as $uni): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($uni['name']); ?></strong>
                                </td>
                                
                                <td>
                                    <?php echo htmlspecialchars($uni['city']); ?>
                                </td>
                                
                                <td>
                                    <?php echo htmlspecialchars($uni['address']); ?>
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
