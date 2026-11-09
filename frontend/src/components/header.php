
<?php
$pageTitle = $pageTitle ?? "UniStay - University Boarding Finder";

$currentPage = basename($_SERVER['PHP_SELF']);
$isOwnerPage = strpos($_SERVER['PHP_SELF'], '/owner/') !== false;

$baseUrl = $isOwnerPage ? '../' : '';
?>

<header class="site-header">

    <div class="container navbar">

        <!-- Website logo -->
        <a href="<?php echo $baseUrl; ?>index.php" class="logo">
            Uni<span>Stay.</span>
        </a>

        <!-- Navigation links -->
        <ul class="nav-links" id="navLinks">

            <li>
                <a href="<?php echo $baseUrl; ?>index.php"
                   class="<?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
                    Home
                </a>
            </li>

            <li>
                <a href="<?php echo $baseUrl; ?>listings.php"
                   class="<?php echo !$isOwnerPage && $currentPage == 'listings.php' ? 'active' : ''; ?>">
                    Find Boarding
                </a>
            </li>

            <?php if ($isOwnerPage): ?>

                <li>
                    <a href="dashboard.php"
                       class="<?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>">
                        Dashboard
                    </a>
                </li>

                <li>
                    <a href="listings.php"
                       class="<?php echo $currentPage == 'listings.php' ? 'active' : ''; ?>">
                        My Listings
                    </a>
                </li>

                <li>
                    <a href="inquiries.php"
                       class="<?php echo $currentPage == 'inquiries.php' ? 'active' : ''; ?>">
                        Inquiries
                    </a>
                </li>

                <li>
                    <a href="tenants.php"
                       class="<?php echo $currentPage == 'tenants.php' ? 'active' : ''; ?>">
                        Tenants
                    </a>
                </li>

                <li>
                    <a href="rent.php"
                       class="<?php echo $currentPage == 'rent.php' ? 'active' : ''; ?>">
                        Rent
                    </a>
                </li>

            <?php else: ?>

                <li>
                    <a href="<?php echo $baseUrl; ?>index.php#about">
                        About Us
                    </a>
                </li>

                <li>
                    <a href="#contact">
                        Contact
                    </a>
                </li>

            <?php endif; ?>

        </ul>

        <!-- Login and registration -->
        <div class="nav-actions">

            <a href="<?php echo $baseUrl; ?>login.php"
               class="btn btn-secondary btn-small">
                Login
            </a>

            <a href="<?php echo $baseUrl; ?>register.php"
               class="btn btn-primary btn-small">
                Get Started
            </a>

        </div>

        <!-- Mobile menu button -->
        <button
            type="button"
            class="menu-toggle"
            id="menuToggle"
            aria-label="Toggle navigation"
            aria-expanded="false"
            aria-controls="navLinks"
        >
            ☰
        </button>

    </div>

</header>
