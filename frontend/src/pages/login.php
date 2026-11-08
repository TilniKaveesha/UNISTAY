
<?php

$pageTitle = "Login | UniStay";

$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email == "" || $password == "") {

        $errorMessage = "Please enter your email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errorMessage = "Please enter a valid email address.";

    } else {

        $errorMessage = "Login is not connected to the backend yet.";

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


<section class="section section-light auth-section">

    <div class="container">

        <div class="auth-container">

            <div class="auth-heading">

                <h2>WELCOME BACK</h2>

                <p>
                    Login to your UniStay account to continue.
                </p>

            </div>


            <?php if ($errorMessage != ""): ?>

                <div class="inquiry-message inquiry-error">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>

            <?php endif; ?>


            <form action="login.php" method="POST" class="auth-form">

                <div class="form-group">

                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="Enter your email"
                        value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">Password</label>

                    <div class="password-wrapper">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter your password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword('password', this)"
                        >
                            Show
                        </button>

                    </div>

                </div>


                <button type="submit" class="btn btn-primary auth-submit">
                    Login
                </button>

            </form>


            <div class="auth-footer">

                <p>
                    Don't have an account?
                    <a href="register.php">Create an account</a>
                </p>

            </div>

        </div>

    </div>

</section>


<?php include __DIR__ . '/../components/footer.php'; ?>


<script>

function togglePassword(inputId, button) {

    const passwordInput = document.getElementById(inputId);

    if (passwordInput.type === "password") {

        passwordInput.type = "text";
        button.textContent = "Hide";

    } else {

        passwordInput.type = "password";
        button.textContent = "Show";

    }

}

</script>

</body>
</html>
