
<?php

$pageTitle = "Create an Account | UniStay";

$errorMessage = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullName = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";
    $role = $_POST["role"] ?? "";

    if (
        $fullName == "" ||
        $email == "" ||
        $phone == "" ||
        $password == "" ||
        $confirmPassword == "" ||
        $role == ""
    ) {

        $errorMessage = "Please fill in all the required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errorMessage = "Please enter a valid email address.";

    } elseif (!in_array($role, ["student", "owner"], true)) {

        $errorMessage = "Please select a valid account type.";

    } elseif ($password != $confirmPassword) {

        $errorMessage = "Your passwords do not match.";

    } elseif (strlen($password) < 8) {

        $errorMessage = "Your password must contain at least 8 characters.";

    } else {

        $successMessage = "Your information has been validated. Account registration will be available once the backend is connected.";

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

        <div class="auth-container register-container">

            <div class="auth-heading">

                <h2>CREATE YOUR ACCOUNT</h2>

                <p>
                    Join UniStay and find or manage university accommodation.
                </p>

            </div>


            <?php if ($errorMessage != ""): ?>

                <div class="inquiry-message inquiry-error">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>

            <?php endif; ?>


            <?php if ($successMessage != ""): ?>

                <div class="inquiry-message inquiry-success">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>

            <?php endif; ?>


            <form action="register.php" method="POST" class="auth-form">


                <div class="form-group">

                    <label for="full_name">Full Name</label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        class="form-control"
                        placeholder="Enter your full name"
                        value="<?php echo htmlspecialchars($_POST["full_name"] ?? ""); ?>"
                        required
                    >

                </div>


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

                    <label for="phone">Phone Number</label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        class="form-control"
                        placeholder="Enter your phone number"
                        value="<?php echo htmlspecialchars($_POST["phone"] ?? ""); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="role">Account Type</label>

                    <select
                        id="role"
                        name="role"
                        class="form-control"
                        required
                    >

                        <option value="">Select Account Type</option>

                        <option value="student"
                            <?php if (($_POST["role"] ?? "") == "student") echo "selected"; ?>>
                            Student
                        </option>

                        <option value="owner"
                            <?php if (($_POST["role"] ?? "") == "owner") echo "selected"; ?>>
                            Boarding Owner
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="password">Password</label>

                    <div class="password-wrapper">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Create a password"
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


                <div class="form-group">

                    <label for="confirm_password">Confirm Password</label>

                    <div class="password-wrapper">

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control"
                            placeholder="Confirm your password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword('confirm_password', this)"
                        >
                            Show
                        </button>

                    </div>

                </div>


                <button type="submit" class="btn btn-primary auth-submit">
                    Create Account
                </button>


            </form>


            <div class="auth-footer">

                <p>
                    Already have an account?
                    <a href="login.php">Login here</a>
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
