
<?php

$pageTitle = "Listing Details | UniStay";

// Temporary demo data
$listing = [
    "id" => 1,
    "name" => "Comfortable Student Boarding",
    "university" => "University of Moratuwa",
    "location" => "Katubedda, Moratuwa",
    "rent" => 15000,
    "capacity" => 2,
    "gender" => "Male",
    "availability" => "available",
    "description" => "A comfortable and convenient boarding place for university students. The property offers a peaceful environment suitable for studying and everyday living.",
    "facilities" => [
        "Wi-Fi",
        "Study Area",
        "Shared Kitchen",
        "Bathroom",
        "Water Supply",
        "Electricity"
    ]
];

$availabilityLabels = [
    "available" => "Available",
    "soon" => "Soon to be Available",
    "unavailable" => "Not Available"
];

$availabilityLabel = $availabilityLabels[$listing["availability"]] ?? "Unknown";

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


<main class="owner-listing-detail-page">

    <div class="container">

        <div class="owner-detail-breadcrumb">
            <a href="dashboard.php">Dashboard</a>
            <span>/</span>
            <a href="listings.php">My Listings</a>
            <span>/</span>
            <span>Listing Details</span>
        </div>


        <section class="owner-detail-heading">

            <div>
                <p class="dashboard-subtitle">PROPERTY DETAILS</p>

                <h1><?php echo htmlspecialchars($listing["name"]); ?></h1>

                <p>
                    <?php echo htmlspecialchars($listing["location"]); ?>
                </p>
            </div>

            <div class="owner-detail-heading-actions">

                <a
                    href="listing_form.php?id=<?php echo $listing["id"]; ?>"
                    class="btn btn-primary"
                >
                    Edit Listing
                </a>

                <a href="listings.php" class="btn btn-outline">
                    Back to Listings
                </a>

            </div>

        </section>


        <section class="owner-detail-layout">

            <div class="owner-detail-main">

                <div class="owner-detail-image">
                    <span>BOARDING PLACE IMAGE</span>
                </div>


                <div class="owner-detail-section">

                    <h2>About This Property</h2>

                    <p>
                        <?php echo htmlspecialchars($listing["description"]); ?>
                    </p>

                </div>


                <div class="owner-detail-section">

                    <h2>Property Information</h2>

                    <div class="owner-detail-info-grid">

                        <div>
                            <span>University</span>
                            <strong>
                                <?php echo htmlspecialchars($listing["university"]); ?>
                            </strong>
                        </div>

                        <div>
                            <span>Location</span>
                            <strong>
                                <?php echo htmlspecialchars($listing["location"]); ?>
                            </strong>
                        </div>

                        <div>
                            <span>Suitable For</span>
                            <strong>
                                <?php echo htmlspecialchars($listing["gender"]); ?>
                            </strong>
                        </div>

                        <div>
                            <span>Maximum Capacity</span>
                            <strong>
                                <?php echo (int)$listing["capacity"]; ?> people
                            </strong>
                        </div>

                    </div>

                </div>


                <div class="owner-detail-section">

                    <h2>Facilities</h2>

                    <div class="owner-detail-facilities">

                        <?php foreach ($listing["facilities"] as $facility): ?>

                            <span>
                                ✓ <?php echo htmlspecialchars($facility); ?>
                            </span>

                        <?php endforeach; ?>

                    </div>

                </div>

            </div>


            <aside class="owner-detail-sidebar">

                <div class="owner-detail-summary">

                    <p class="dashboard-subtitle">LISTING SUMMARY</p>

                    <div class="owner-detail-price">
                        <span>Monthly Rent</span>

                        <strong>
                            Rs. <?php echo number_format($listing["rent"], 2); ?>
                        </strong>
                    </div>


                    <div class="owner-detail-status-row">

                        <span>Availability</span>

                        <span class="availability-status <?php echo htmlspecialchars($listing["availability"]); ?>">
                            <?php echo htmlspecialchars($availabilityLabel); ?>
                        </span>

                    </div>


                    <div class="owner-detail-status-row">
                        <span>Listing ID</span>
                        <strong>#<?php echo (int)$listing["id"]; ?></strong>
                    </div>


                    <div class="owner-detail-status-row">
                        <span>Capacity</span>
                        <strong><?php echo (int)$listing["capacity"]; ?></strong>
                    </div>


                    <a
                        href="listing_form.php?id=<?php echo $listing["id"]; ?>"
                        class="btn btn-primary owner-detail-edit-button"
                    >
                        Edit Listing
                    </a>

                    <a
                        href="inquiries.php"
                        class="btn btn-outline owner-detail-edit-button"
                    >
                        View Inquiries
                    </a>

                </div>


                <div class="owner-detail-note">

                    <strong>Reminder</strong>

                    <p>
                        Keep your listing availability and rent information
                        updated so students can see accurate details.
                    </p>

                </div>

            </aside>

        </section>

    </div>

</main>


<?php include __DIR__ . '/../../components/footer.php'; ?>

</body>
</html>
