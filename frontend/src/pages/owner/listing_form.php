
<?php

$pageTitle = "Add Boarding Listing | UniStay";

$errorMessage = "";
$successMessage = "";

// Temporary demo listing for editing
$existingListing = [
    "name" => "Comfortable Student Boarding",
    "university" => "University of Moratuwa",
    "location" => "Katubedda, Moratuwa",
    "rent" => 15000,
    "capacity" => 2,
    "gender" => "male",
    "availability" => "available",
    "description" => "A comfortable boarding place suitable for university students.",
    "facilities" => "Wi-Fi, Study Area, Shared Kitchen, Water"
];

$isEditing = isset($_GET["id"]);

$formData = [
    "name" => $isEditing ? $existingListing["name"] : "",
    "university" => $isEditing ? $existingListing["university"] : "",
    "location" => $isEditing ? $existingListing["location"] : "",
    "rent" => $isEditing ? $existingListing["rent"] : "",
    "capacity" => $isEditing ? $existingListing["capacity"] : "",
    "gender" => $isEditing ? $existingListing["gender"] : "",
    "availability" => $isEditing ? $existingListing["availability"] : "available",
    "description" => $isEditing ? $existingListing["description"] : "",
    "facilities" => $isEditing ? $existingListing["facilities"] : ""
];

// Keep submitted values if the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    foreach ($formData as $key => $value) {
        $formData[$key] = trim($_POST[$key] ?? "");
    }

    if (
        $formData["name"] === "" ||
        $formData["university"] === "" ||
        $formData["location"] === "" ||
        $formData["rent"] === "" ||
        $formData["capacity"] === "" ||
        $formData["gender"] === "" ||
        $formData["availability"] === "" ||
        $formData["description"] === ""
    ) {

        $errorMessage = "Please fill in all required fields.";

    } elseif (
        !is_numeric($formData["rent"]) ||
        $formData["rent"] <= 0
    ) {

        $errorMessage = "Please enter a valid monthly rent.";

    } elseif (
        filter_var($formData["capacity"], FILTER_VALIDATE_INT) === false ||
        $formData["capacity"] < 1
    ) {

        $errorMessage = "Please enter a valid capacity.";

    } elseif (!in_array($formData["gender"], ["male", "female", "any"], true)) {

        $errorMessage = "Please select a valid gender suitability.";

    } elseif (!in_array($formData["availability"], ["available", "soon", "unavailable"], true)) {

        $errorMessage = "Please select a valid availability status.";

    } else {

        $successMessage = $isEditing
            ? "Your listing information has been validated. Database updating will be available later."
            : "Your listing information has been validated. Database saving will be available later.";

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


<main class="listing-form-page">

    <div class="container">

        <!-- Page heading -->
        <section class="listing-form-heading">

            <div>
                <p class="dashboard-subtitle">PROPERTY MANAGEMENT</p>

                <h1>
                    <?php echo $isEditing ? "Edit Listing" : "Add New Listing"; ?>
                </h1>

                <p>
                    <?php echo $isEditing
                        ? "Update the information about your boarding place."
                        : "Provide the details of your boarding place to attract students."; ?>
                </p>
            </div>

            <a href="listings.php" class="btn btn-outline">
                Back to My Listings
            </a>

        </section>


        <section class="listing-form-container">

            <?php if ($errorMessage !== ""): ?>

                <div class="inquiry-message inquiry-error">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>

            <?php endif; ?>


            <?php if ($successMessage !== ""): ?>

                <div class="inquiry-message inquiry-success">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>

            <?php endif; ?>


            <form action="" method="POST" class="boarding-listing-form">

                <!-- Basic information -->
                <div class="listing-form-section">

                    <div class="listing-form-section-heading">

                        <h2>Basic Information</h2>

                        <p>Enter the main details of your boarding place.</p>

                    </div>


                    <div class="listing-form-grid">

                        <div class="form-group full-width">

                            <label for="name">Boarding Place Name *</label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control"
                                placeholder="Enter the boarding place name"
                                value="<?php echo htmlspecialchars($formData["name"]); ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="university">Nearby University *</label>

                            <select
                                id="university"
                                name="university"
                                class="form-control"
                                required
                            >

                                <option value="">Select University</option>

                                <?php
                                $universities = [
                                    "University of Moratuwa",
                                    "University of Colombo",
                                    "University of Sri Jayewardenepura",
                                    "University of Kelaniya",
                                    "University of Peradeniya",
                                    "University of Ruhuna"
                                ];

                                foreach ($universities as $university):
                                ?>

                                    <option
                                        value="<?php echo htmlspecialchars($university); ?>"
                                        <?php if ($formData["university"] === $university) echo "selected"; ?>
                                    >
                                        <?php echo htmlspecialchars($university); ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="location">Location *</label>

                            <input
                                type="text"
                                id="location"
                                name="location"
                                class="form-control"
                                placeholder="Enter area or town"
                                value="<?php echo htmlspecialchars($formData["location"]); ?>"
                                required
                            >

                        </div>


                        <div class="form-group full-width">

                            <label for="description">Description *</label>

                            <textarea
                                id="description"
                                name="description"
                                class="form-control"
                                rows="5"
                                placeholder="Describe your boarding place..."
                                required
                            ><?php echo htmlspecialchars($formData["description"]); ?></textarea>

                        </div>

                    </div>

                </div>


                <!-- Property details -->
                <div class="listing-form-section">

                    <div class="listing-form-section-heading">

                        <h2>Property Details</h2>

                        <p>Specify the rent, capacity and suitable boarders.</p>

                    </div>


                    <div class="listing-form-grid">

                        <div class="form-group">

                            <label for="rent">Monthly Rent (Rs.) *</label>

                            <input
                                type="number"
                                id="rent"
                                name="rent"
                                class="form-control"
                                min="1"
                                step="0.01"
                                placeholder="e.g. 15000"
                                value="<?php echo htmlspecialchars((string)$formData["rent"]); ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="capacity">Maximum Capacity *</label>

                            <input
                                type="number"
                                id="capacity"
                                name="capacity"
                                class="form-control"
                                min="1"
                                placeholder="e.g. 2"
                                value="<?php echo htmlspecialchars((string)$formData["capacity"]); ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="gender">Suitable For *</label>

                            <select
                                id="gender"
                                name="gender"
                                class="form-control"
                                required
                            >

                                <option value="">Select Gender</option>

                                <option value="male" <?php if ($formData["gender"] === "male") echo "selected"; ?>>
                                    Male
                                </option>

                                <option value="female" <?php if ($formData["gender"] === "female") echo "selected"; ?>>
                                    Female
                                </option>

                                <option value="any" <?php if ($formData["gender"] === "any") echo "selected"; ?>>
                                    Any Gender
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="availability">Availability *</label>

                            <select
                                id="availability"
                                name="availability"
                                class="form-control"
                                required
                            >

                                <option value="available" <?php if ($formData["availability"] === "available") echo "selected"; ?>>
                                    Available
                                </option>

                                <option value="soon" <?php if ($formData["availability"] === "soon") echo "selected"; ?>>
                                    Soon to be Available
                                </option>

                                <option value="unavailable" <?php if ($formData["availability"] === "unavailable") echo "selected"; ?>>
                                    Not Available
                                </option>

                            </select>

                        </div>


                        <div class="form-group full-width">

                            <label for="facilities">Facilities</label>

                            <textarea
                                id="facilities"
                                name="facilities"
                                class="form-control"
                                rows="3"
                                placeholder="e.g. Wi-Fi, Study Area, Kitchen, Parking"
                            ><?php echo htmlspecialchars($formData["facilities"]); ?></textarea>

                            <small>
                                Separate facilities using commas.
                            </small>

                        </div>

                    </div>

                </div>


                <!-- Form actions -->
                <div class="listing-form-actions">

                    <a href="listings.php" class="btn btn-outline">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary">

                        <?php echo $isEditing ? "Save Changes" : "Create Listing"; ?>

                    </button>

                </div>

            </form>

        </section>

    </div>

</main>


<?php include __DIR__ . '/../../components/footer.php'; ?>

</body>
</html>
