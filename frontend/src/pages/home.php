
<?php
$pageTitle = "UniStay - Find Your Perfect Boarding Place";
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="/UNISTAY/frontend/src/styles/style.css">
</head>

<body>

<?php include __DIR__ . '/../components/header.php'; ?>


<!--HERO SECTION-->

<section class="hero fade-in">

    <div class="container">

        <div class="hero-grid">

            <div class="hero-content">

                <h1>
                    FIND YOUR PERFECT
                    <span>UNIVERSITY BOARDING PLACE.</span>
                </h1>

                <p>
                    Discover comfortable and affordable boarding
                    places near your university.

                    Compare options, explore locations and find
                    a place that feels like home.
                </p>

                <div class="hero-buttons">

                    <a href="listings.php" class="btn btn-primary">
                        Explore Boarding
                    </a>

                    <a href="#about" class="btn btn-secondary">
                        Learn More
                    </a>

                </div>

            </div>


            <!-- Temporary image placeholder -->

            <div class="hero-image"
                 style="
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    background:#eeeeee;
                    color:#777777;
                    text-align:center;
                    padding:20px;
                 ">

                <div>
                    <h2>YOUR NEW PLACE</h2>
                    <p>Accommodation near your university</p>
                </div>

            </div>

        </div>


        <!--SEARCH FORM-->

        <div class="search-panel">

            <form action="listings.php"
                  method="GET"
                  class="search-form">

                <div class="form-group">

                    <label for="university">
                        Select University
                    </label>

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

                    <label for="gender">
                        Gender Preference
                    </label>

                    <select
                        name="gender"
                        id="gender"
                        class="form-control"
                    >

                        <option value="">All Categories</option>

                        <option value="male">Male</option>

                        <option value="female">Female</option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="availability">
                        Availability
                    </label>

                    <select
                        name="availability"
                        id="availability"
                        class="form-control"
                    >

                        <option value="">All Availability</option>

                        <option value="available">
                            Available
                        </option>

                        <option value="soon">
                            Soon to be Available
                        </option>

                        <option value="unavailable">
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

<!-- FEATURED LISTINGS -->

<section class="section section-light">

    <div class="container">

        <div class="section-heading">

            <h2>EXPLORE BOARDING PLACES</h2>

            <p>
                Find accommodation options that suit your
                university life.
            </p>

        </div>

        <div class="section-heading">

            <h3>No Boarding Places Available Yet</h3>

            <p>
                Boarding listings will appear here once
                owners start adding their properties.
            </p>

        </div>

    </div>

</section>


<!--ABOUT UNISTAY-->

<section class="section" id="about">

    <div class="container">

        <div class="section-heading">

            <h2>WHY CHOOSE UNISTAY?</h2>

            <p>
                Making university accommodation searching
                easier and more convenient.
            </p>

        </div>


        <div class="feature-grid">


            <div class="feature-card">

                <div class="feature-icon">
                    01
                </div>

                <h3>Easy Discovery</h3>

                <p>
                    Find boarding places near your university
                    without searching through multiple sources.
                </p>

            </div>


            <div class="feature-card">

                <div class="feature-icon">
                    02
                </div>

                <h3>Compare Options</h3>

                <p>
                    Explore different accommodation options
                    based on rent, location and suitability.
                </p>

            </div>


            <div class="feature-card">

                <div class="feature-icon">
                    03
                </div>

                <h3>Direct Inquiries</h3>

                <p>
                    Connect with boarding owners and submit
                    inquiries about available accommodation.
                </p>

            </div>


        </div>

    </div>

</section>


<?php include __DIR__ . '/../components/header.php'; ?>
    </body>
</html>