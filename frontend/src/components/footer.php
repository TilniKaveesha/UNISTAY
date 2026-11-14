
<footer class="site-footer" id="contact">

    <div class="container">

        <div class="footer-grid">

            <!-- Company information -->
            <div class="footer-column">

                <h3>UniStay.</h3>

                <p>
                    Finding the right boarding place near your
                    university has never been easier.

                    Explore accommodation options, compare
                    prices and connect with boarding owners.
                </p>

            </div>

            <!-- Quick links -->
            <div class="footer-column">

                <h3>Quick Links</h3>

                <ul>
                    <li>
                        <a href="<?php echo $baseUrl; ?>index.php">
                            Home
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo $baseUrl; ?>listings.php">
                            Find Boarding
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo $baseUrl; ?>index.php#about">
                            About Us
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo $baseUrl; ?>login.php">
                            Login
                        </a>
                    </li>
                </ul>

            </div>

            <!-- Contact information -->
            <div class="footer-column">

                <h3>Contact Us</h3>

                <ul>
                    <li>Email: support@unistay.lk</li>
                    <li>University Boarding Finder</li>
                    <li>Sri Lanka</li>
                </ul>

            </div>

        </div>

        <div class="footer-bottom">

            <p>
                &copy; <?php echo date("Y"); ?> UniStay.
                All Rights Reserved.
            </p>

        </div>

    </div>

</footer>

<?php
$scriptPath = $isOwnerPage
    ? '../../src/assets/main.js'
    : '../src/assets/main.js';
?>

<script src="<?php echo $scriptPath; ?>"></script>

</body>
</html>
