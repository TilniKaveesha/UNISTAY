
<?php

$pageTitle = "My Listings | UniStay";

// Temporary demo listings
$boardingListings = [
    [
        "id" => 1,
        "name" => "Comfortable Student Boarding",
        "university" => "University of Moratuwa",
        "location" => "Katubedda, Moratuwa",
        "rent" => 15000,
        "gender" => "Male",
        "availability" => "available",
        "capacity" => 2
    ],
    [
        "id" => 2,
        "name" => "Girls Boarding Near Campus",
        "university" => "University of Moratuwa",
        "location" => "Rawathawatta, Moratuwa",
        "rent" => 18000,
        "gender" => "Female",
        "availability" => "soon",
        "capacity" => 3
    ],
    [
        "id" => 3,
        "name" => "Shared Student Room",
        "university" => "University of Colombo",
        "location" => "Colombo 05",
        "rent" => 12000,
        "gender" => "Any",
        "availability" => "unavailable",
        "capacity" => 2
    ]
];

$search = trim($_GET["search"] ?? "");
$availabilityFilter = $_GET["availability"] ?? "";

$filteredListings = [];

foreach ($boardingListings as $listing) {

    $matchesSearch =
        $search === "" ||
        stripos($listing["name"], $search) !== false ||
        stripos($listing["university"], $search) !== false ||
        stripos($listing["location"], $search) !== false;

    $matchesAvailability =
        $availabilityFilter === "" ||
        $listing["availability"] === $availabilityFilter;

    if ($matchesSearch && $matchesAvailability) {
        $filteredListings[] = $listing;
    }
}

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


<main class="owner-listings-page">

    <div class="container">

        <!-- Page heading -->
        <section class="owner-listings-heading">

            <div>
                <p class="dashboard-subtitle">PROPERTY MANAGEMENT</p>
                <h1>My Listings</h1>

                <p>
                    View and manage all your registered boarding places.
                </p>
            </div>

            <a href="listing_form.php" class="btn btn-primary">
                + Add New Listing
            </a>

        </section>


        <!-- Listing summary -->
        <section class="owner-listing-summary">

            <div class="owner-listing-summary-card">

                <span>Total Listings</span>

                <h2><?php echo count($boardingListings); ?></h2>

            </div>

            <div class="owner-listing-summary-card">

                <span>Available</span>

                <h2>
                    <?php
                    echo count(array_filter($boardingListings, function ($listing) {
                        return $listing["availability"] === "available";
                    }));
                    ?>
                </h2>

            </div>

            <div class="owner-listing-summary-card">

                <span>Soon Available</span>

                <h2>
                    <?php
                    echo count(array_filter($boardingListings, function ($listing) {
                        return $listing["availability"] === "soon";
                    }));
                    ?>
                </h2>

            </div>

            <div class="owner-listing-summary-card">

                <span>Unavailable</span>

                <h2>
                    <?php
                    echo count(array_filter($boardingListings, function ($listing) {
                        return $listing["availability"] === "unavailable";
                    }));
                    ?>
                </h2>

            </div>

        </section>


        <!-- Search and filters -->
        <section class="owner-listing-filters">

            <form method="GET" action="listings.php">

                <div class="owner-listing-search">

                    <input
                        type="text"
                        name="search"
                        placeholder="Search by listing, university or location..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                    <button type="submit" class="btn btn-primary">
                        Search
                    </button>

                </div>


                <div class="owner-listing-filter-select">

                    <select name="availability">

                        <option value="">All Availability</option>

                        <option value="available"
                            <?php if ($availabilityFilter === "available") echo "selected"; ?>>
                            Available
                        </option>

                        <option value="soon"
                            <?php if ($availabilityFilter === "soon") echo "selected"; ?>>
                            Soon to be Available
                        </option>

                        <option value="unavailable"
                            <?php if ($availabilityFilter === "unavailable") echo "selected"; ?>>
                            Not Available
                        </option>

                    </select>

                    <button type="submit" class="btn btn-outline">
                        Filter
                    </button>

                </div>

            </form>

        </section>


        <!-- Listings -->
        <section class="owner-listings-results">

            <div class="owner-listings-results-heading">

                <h2>Your Boarding Places</h2>

                <span>
                    <?php echo count($filteredListings); ?> results
                </span>

            </div>


            <?php if (count($filteredListings) > 0): ?>

                <div class="owner-listings-grid">

                    <?php foreach ($filteredListings as $listing): ?>

                        <article class="owner-listing-card">

                            <div class="owner-listing-card-image">

                                <span class="owner-listing-placeholder">
                                    BOARDING PLACE
                                </span>

                            </div>


                            <div class="owner-listing-card-content">

                                <div class="owner-listing-card-top">

                                    <span class="availability-status <?php echo htmlspecialchars($listing["availability"]); ?>">

                                        <?php
                                        if ($listing["availability"] === "available") {
                                            echo "Available";
                                        } elseif ($listing["availability"] === "soon") {
                                            echo "Soon Available";
                                        } else {
                                            echo "Not Available";
                                        }
                                        ?>

                                    </span>

                                    <span class="owner-listing-id">
                                        #<?php echo $listing["id"]; ?>
                                    </span>

                                </div>


                                <h3>
                                    <?php echo htmlspecialchars($listing["name"]); ?>
                                </h3>

                                <p class="owner-listing-university">
                                    <?php echo htmlspecialchars($listing["university"]); ?>
                                </p>

                                <p class="owner-listing-location">
                                    <?php echo htmlspecialchars($listing["location"]); ?>
                                </p>


                                <div class="owner-listing-details">

                                    <span>
                                        <?php echo htmlspecialchars($listing["gender"]); ?>
                                    </span>

                                    <span>
                                        <?php echo $listing["capacity"]; ?> people
                                    </span>

                                </div>


                                <div class="owner-listing-card-bottom">

                                    <div>
                                        <span>Monthly Rent</span>

                                        <strong>
                                            Rs. <?php echo number_format($listing["rent"]); ?>
                                        </strong>
                                    </div>

                                </div>


                                <div class="owner-listing-actions">

                                    <a
                                        href="listing_form.php?id=<?php echo $listing["id"]; ?>"
                                        class="btn btn-outline"
                                    >
                                        Edit
                                    </a>

                                    <a
                                        href="listing.php?id=<?php echo $listing["id"]; ?>"
                                        class="btn btn-dark"
                                    >
                                        View
                                    </a>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="owner-listings-empty">

                    <h3>No Listings Found</h3>

                    <p>
                        Try changing your search or availability filter.
                    </p>

                    <a href="listings.php" class="btn btn-outline">
                        Clear Filters
                    </a>

                </div>

            <?php endif; ?>

        </section>

    </div>

</main>


<?php include __DIR__ . '/../../components/footer.php'; ?>

</body>
</html>
