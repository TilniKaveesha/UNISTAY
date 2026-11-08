
<?php

$pageTitle = "Boarding Details | UniStay";

// Temporary demonstration data
$boardingPlace = [
    "id" => 1,
    "name" => "Comfortable Student Boarding",
    "university" => "University of Moratuwa",
    "location" => "Katubedda, Moratuwa",
    "rent" => 15000,
    "gender" => "Male",
    "availability" => "available",
    "capacity" => 2,
    "description" => "A comfortable and peaceful boarding place suitable for university students. The property is located close to the university and provides a convenient environment for studying and daily activities.",
    "facilities" => [
        "Wi-Fi Access",
        "Study Area",
        "Shared Kitchen",
        "Bathroom",
        "Water Supply",
        "Electricity"
    ],
    "owner_name" => "Boarding Owner",
    "owner_phone" => "077 123 4567"
];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="../src/styles/style.css">

</head>

<body>

<?php include __DIR__ . '/../components/header.php'; ?>


<!-- LISTING DETAILS -->

<section class="section section-light">

    <div class="container">

        <!-- BREADCRUMB -->

        <div class="listing-breadcrumb">

            <a href="index.php">Home</a>

            <span>/</span>

            <a href="listings.php">Boarding Places</a>

            <span>/</span>

            <span>Details</span>

        </div>


        <!-- PAGE HEADING -->

        <div class="section-heading">

            <h2><?php echo htmlspecialchars($boardingPlace["name"]); ?></h2>

            <p>
                <?php echo htmlspecialchars($boardingPlace["location"]); ?>
            </p>

        </div>


        <!-- IMAGE PLACEHOLDER -->

        <div class="listing-detail-image"
             style="
                background:#e7e7e7;
                display:flex;
                align-items:center;
                justify-content:center;
                color:#777777;
                min-height:300px;
                text-align:center;
                margin-bottom:35px;
             ">

            <div>

                <h2>BOARDING PLACE IMAGE</h2>

                <p>Property photos will appear here</p>

            </div>

        </div>


        <!-- DETAILS GRID -->

        <div class="listing-detail-grid">


            <!-- MAIN INFORMATION -->

            <div class="listing-detail-content">

                <div class="listing-detail-section">

                    <h3>About This Boarding Place</h3>

                    <p>
                        <?php echo htmlspecialchars($boardingPlace["description"]); ?>
                    </p>

                </div>


                <div class="listing-detail-section">

                    <h3>Property Information</h3>

                    <div class="detail-info-grid">

                        <div class="detail-info-item">

                            <span>University</span>

                            <strong>
                                <?php echo htmlspecialchars($boardingPlace["university"]); ?>
                            </strong>

                        </div>


                        <div class="detail-info-item">

                            <span>Location</span>

                            <strong>
                                <?php echo htmlspecialchars($boardingPlace["location"]); ?>
                            </strong>

                        </div>


                        <div class="detail-info-item">

                            <span>Gender Suitability</span>

                            <strong>
                                <?php echo htmlspecialchars($boardingPlace["gender"]); ?>
                            </strong>

                        </div>


                        <div class="detail-info-item">

                            <span>Capacity</span>

                            <strong>
                                <?php echo htmlspecialchars($boardingPlace["capacity"]); ?> Students
                            </strong>

                        </div>

                    </div>

                </div>


                <!-- FACILITIES -->

                <div class="listing-detail-section">

                    <h3>Facilities</h3>

                    <div class="facilities-grid">

                        <?php foreach ($boardingPlace["facilities"] as $facility): ?>

                            <div class="facility-item">

                                <span>✓</span>

                                <?php echo htmlspecialchars($facility); ?>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            </div>


            <!-- BOOKING / INQUIRY CARD -->

            <div class="listing-detail-sidebar">

                <div class="detail-price-card">

                    <div class="detail-status">

                        <?php if ($boardingPlace["availability"] == "available"): ?>

                            <span class="status status-available">
                                Available
                            </span>

                        <?php elseif ($boardingPlace["availability"] == "soon"): ?>

                            <span class="status status-soon">
                                Soon to be Available
                            </span>

                        <?php else: ?>

                            <span class="status status-unavailable">
                                Not Available
                            </span>

                        <?php endif; ?>

                    </div>


                    <div class="detail-rent">

                        Rs. <?php echo number_format($boardingPlace["rent"]); ?>

                        <span>/ month</span>

                    </div>


                    <div class="detail-owner">

                        <h3>Owner Information</h3>

                        <p>
                            <?php echo htmlspecialchars($boardingPlace["owner_name"]); ?>
                        </p>

                        <p>
                            <?php echo htmlspecialchars($boardingPlace["owner_phone"]); ?>
                        </p>

                    </div>


                    <a
                        href="inquiry.php?listing_id=<?php echo $boardingPlace["id"]; ?>"
                        class="btn btn-primary"
                        style="width:100%;"
                    >
                        Send an Inquiry
                    </a>


                    <p class="detail-note">

                        Contact the boarding owner for more
                        information about this property.

                    </p>

                </div>

            </div>


        </div>

    </div>

</section>


<?php include __DIR__ . '/../components/footer.php'; ?>

</body>
</html>
