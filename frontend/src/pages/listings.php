<?php

$pageTitle = "Explore Boarding Places | UniStay";

//Featured boarding places
$boardingPlaces = [];


// Get search values from the URL
$selectedUniversity = isset($_GET["university"])
    ? $_GET["university"]
    : "";

$selectedGender = isset($_GET["gender"])
    ? $_GET["gender"]
    : "";

$selectedAvailability = isset($_GET["availability"])
    ? $_GET["availability"]
    : "";


// Filter the boarding places
$filteredPlaces = [];

foreach ($boardingPlaces as $place) {

    // Check university
    if (
        $selectedUniversity != "" &&
        $place["university"] != $selectedUniversity
    ) {
        continue;
    }

    // Check gender
    if ($selectedGender != "") {

        if (
            $place["gender"] != $selectedGender &&
            $place["gender"] != "both"
        ) {
            continue;
        }
    }

    // Check availability
    if (
        $selectedAvailability != "" &&
        $place["availability"] != $selectedAvailability
    ) {
        continue;
    }

    $filteredPlaces[] = $place;
}

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


<!-- PAGE INTRODUCTION -->

<section class="section section-light">

    <div class="container">

        <div class="section-heading">

            <h2>FIND YOUR IDEAL BOARDING PLACE</h2>

            <p>
                Explore accommodation options near your university
                and find a place that suits your needs.
            </p>

        </div>


        <!-- SEARCH AND FILTER FORM -->

        <div class="search-panel">

            <form action="listings.php" method="GET" class="search-form">

                <div class="form-group">

                    <label for="university">University</label>

                    <select
                        name="university"
                        id="university"
                        class="form-control"
                        disabled
                    >
                        <option value="">Universities will appear here</option>
                    </select>

                </div>


                <div class="form-group">

                    <label for="gender">Gender Preference</label>

                    <select name="gender" id="gender" class="form-control">

                        <option value="">All Categories</option>

                        <option value="male"
                            <?php if ($selectedGender == "male") echo "selected"; ?>>
                            Male
                        </option>

                        <option value="female"
                            <?php if ($selectedGender == "female") echo "selected"; ?>>
                            Female
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="availability">Availability</label>

                    <select name="availability" id="availability" class="form-control">

                        <option value="">All Availability</option>

                        <option value="available"
                            <?php if ($selectedAvailability == "available") echo "selected"; ?>>
                            Available
                        </option>

                        <option value="soon"
                            <?php if ($selectedAvailability == "soon") echo "selected"; ?>>
                            Soon to be Available
                        </option>

                        <option value="unavailable"
                            <?php if ($selectedAvailability == "unavailable") echo "selected"; ?>>
                            Not Available
                        </option>

                    </select>

                </div>


                <button type="submit" class="btn btn-primary">
                    Search Places
                </button>

            </form>

        </div>

    </div>

</section>


<!-- SEARCH RESULTS -->

<section class="section">

    <div class="container">

        <div class="section-heading">

            <h2>BOARDING PLACES</h2>

            <p>
                <?php echo count($filteredPlaces); ?>
                boarding place(s) found.
            </p>

        </div>


        <?php if (count($filteredPlaces) > 0): ?>

            <div class="listing-grid">

                <?php foreach ($filteredPlaces as $place): ?>

                    <div class="listing-card">

                        <!-- Temporary image placeholder -->

                        <div class="listing-image"
                             style="background:#e7e7e7;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    color:#777;">

                            Boarding Image

                        </div>


                        <div class="listing-content">

                            <?php if ($place["availability"] == "available"): ?>

                                <span class="status status-available">
                                    Available
                                </span>

                            <?php elseif ($place["availability"] == "soon"): ?>

                                <span class="status status-soon">
                                    Soon to be Available
                                </span>

                            <?php else: ?>

                                <span class="status status-unavailable">
                                    Not Available
                                </span>

                            <?php endif; ?>


                            <h3>
                                <?php echo htmlspecialchars($place["name"]); ?>
                            </h3>


                            <p class="listing-location">

                                <?php echo htmlspecialchars($place["location"]); ?>

                                <br>

                                Near <?php echo htmlspecialchars($place["university_name"]); ?>

                            </p>


                            <div class="listing-price">

                                Rs. <?php echo number_format($place["rent"]); ?>

                                <span>/ month</span>

                            </div>


                            <div class="listing-meta">

                                <span class="status status-available">

                                    <?php

                                    if ($place["gender"] == "both") {
                                        echo "Male / Female";
                                    } else {
                                        echo ucfirst($place["gender"]);
                                    }

                                    ?>

                                </span>

                            </div>


                            <a href="listing.php?id=<?php echo $place["id"]; ?>"
                               class="btn btn-primary"
                               style="width:100%;">

                                View Details

                            </a>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>


        <?php else: ?>

            <div class="section-heading">

                <h3>No Boarding Places Available Yet</h3>

                <p>
                    Boarding listings will appear here once owners
                    start adding their properties.
                </p>

                <a href="listings.php" class="btn btn-primary">
                    Clear Filters
                </a>

            </div>

        <?php endif; ?>

    </div>

</section>


<?php include __DIR__ . '/../components/footer.php'; ?>

</body>
</html>