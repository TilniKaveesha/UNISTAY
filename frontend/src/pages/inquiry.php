
<?php

$pageTitle = "Send an Inquiry | UniStay";

$listingId = isset($_GET["listing_id"])
    ? (int) $_GET["listing_id"]
    : 1;

// Temporary demonstration data
$boardingPlace = [
    "id" => 1,
    "name" => "Comfortable Student Boarding",
    "university" => "University of Moratuwa",
    "location" => "Katubedda, Moratuwa",
    "rent" => 15000,
    "owner_name" => "Boarding Owner"
];

$successMessage = "";
$errorMessage = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $studentName = trim($_POST["student_name"] ?? "");
    $studentEmail = trim($_POST["student_email"] ?? "");
    $studentPhone = trim($_POST["student_phone"] ?? "");
    $studentGender = $_POST["student_gender"] ?? "";
    $studentUniversity = trim($_POST["student_university"] ?? "");
    $message = trim($_POST["message"] ?? "");

    if (
        $studentName == "" ||
        $studentEmail == "" ||
        $studentPhone == "" ||
        $studentGender == "" ||
        $studentUniversity == "" ||
        $message == ""
    ) {

        $errorMessage = "Please fill in all the required fields.";

    } elseif (!filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {

        $errorMessage = "Please enter a valid email address.";

    } else {

        $successMessage = "Your inquiry has been submitted successfully. This is a demonstration only; your information has not been saved.";

    }
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


<!-- INQUIRY PAGE -->

<section class="section section-light">

    <div class="container">

        <!-- BREADCRUMB -->

        <div class="listing-breadcrumb">

            <a href="index.php">Home</a>

            <span>/</span>

            <a href="listings.php">Boarding Places</a>

            <span>/</span>

            <a href="listing.php?id=<?php echo $listingId; ?>">
                Boarding Details
            </a>

            <span>/</span>

            <span>Inquiry</span>

        </div>


        <div class="section-heading">

            <h2>SEND AN INQUIRY</h2>

            <p>
                Interested in this boarding place?
                Send an inquiry to the owner.
            </p>

        </div>


        <div class="inquiry-layout">


            <!-- SELECTED BOARDING INFORMATION -->

            <div class="inquiry-property-card">

                <div class="inquiry-property-image">

                    <h3>BOARDING PLACE</h3>

                    <p>Property image will appear here</p>

                </div>


                <div class="inquiry-property-content">

                    <span class="status status-available">
                        Available
                    </span>

                    <h3>
                        <?php echo htmlspecialchars($boardingPlace["name"]); ?>
                    </h3>

                    <p>
                        <?php echo htmlspecialchars($boardingPlace["location"]); ?>
                    </p>

                    <p>
                        Near <?php echo htmlspecialchars($boardingPlace["university"]); ?>
                    </p>

                    <div class="inquiry-property-price">

                        Rs. <?php echo number_format($boardingPlace["rent"]); ?>

                        <span>/ month</span>

                    </div>

                    <a
                        href="listing.php?id=<?php echo $listingId; ?>"
                        class="btn btn-secondary"
                        style="width:100%;"
                    >
                        View Property Details
                    </a>

                </div>

            </div>


            <!-- INQUIRY FORM -->

            <div class="inquiry-form-container">

                <h3>Your Information</h3>

                <p class="inquiry-description">
                    Please provide your details so the boarding
                    owner can contact you.
                </p>


                <?php if ($successMessage != ""): ?>

                    <div class="inquiry-message inquiry-success">

                        <?php echo htmlspecialchars($successMessage); ?>

                    </div>

                <?php endif; ?>


                <?php if ($errorMessage != ""): ?>

                    <div class="inquiry-message inquiry-error">

                        <?php echo htmlspecialchars($errorMessage); ?>

                    </div>

                <?php endif; ?>


                <form action="inquiry.php?listing_id=<?php echo $listingId; ?>"
                      method="POST"
                      class="inquiry-form">


                    <div class="form-group">

                        <label for="student_name">
                            Full Name
                        </label>

                        <input
                            type="text"
                            id="student_name"
                            name="student_name"
                            class="form-control"
                            placeholder="Enter your full name"
                            value="<?php echo htmlspecialchars($_POST["student_name"] ?? ""); ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="student_email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="student_email"
                            name="student_email"
                            class="form-control"
                            placeholder="Enter your email address"
                            value="<?php echo htmlspecialchars($_POST["student_email"] ?? ""); ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="student_phone">
                            Phone Number
                        </label>

                        <input
                            type="tel"
                            id="student_phone"
                            name="student_phone"
                            class="form-control"
                            placeholder="Enter your phone number"
                            value="<?php echo htmlspecialchars($_POST["student_phone"] ?? ""); ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="student_university">
                            University
                        </label>

                        <input
                            type="text"
                            id="student_university"
                            name="student_university"
                            class="form-control"
                            placeholder="Enter your university"
                            value="<?php echo htmlspecialchars($_POST["student_university"] ?? ""); ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="student_gender">
                            Gender
                        </label>

                        <select
                            id="student_gender"
                            name="student_gender"
                            class="form-control"
                            required
                        >

                            <option value="">Select Gender</option>

                            <option value="male"
                                <?php if (($_POST["student_gender"] ?? "") == "male") echo "selected"; ?>>
                                Male
                            </option>

                            <option value="female"
                                <?php if (($_POST["student_gender"] ?? "") == "female") echo "selected"; ?>>
                                Female
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="message">
                            Inquiry Message
                        </label>

                        <textarea
                            id="message"
                            name="message"
                            class="form-control"
                            rows="5"
                            placeholder="Write a message to the boarding owner..."
                            required
                        ><?php echo htmlspecialchars($_POST["message"] ?? ""); ?></textarea>

                    </div>


                    <button type="submit"
                            class="btn btn-primary"
                            style="width:100%;">

                        Submit Inquiry

                    </button>


                    <p class="inquiry-form-note">

                        Your inquiry will be reviewed by the
                        boarding owner.

                    </p>

                </form>

            </div>

        </div>

    </div>

</section>


<?php include __DIR__ . '/../components/footer.php'; ?>

</body>
</html>
