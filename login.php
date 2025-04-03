<!-- login.php -->
<?php include 'includes/header.php'; ?>

<div class="login-container">
    <div class="login-card">
        <div class="school-brand">
            <img src="assets/images/logo.png" alt="School Logo">
            <h1>Flipper School CRM</h1>
        </div>

        <form id="loginForm" action="backend/login_handler.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required 
                    placeholder="parent@example.com">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                    placeholder="••••••••">
            </div>

            <button type="submit" class="btn-login">
                <span class="spinner-border spinner-border-sm d-none"></span>
                Sign In
            </button>
        </form>

        <div class="login-links">
            <a href="forgot_password.php">Forgot Password?</a>
            <span>|</span>
            <a href="register.php">Create Account</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>