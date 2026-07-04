<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <!-- FIXED PATHS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/auth/auth.css">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome (FIXED) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- JS libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>

<body>

    <div class="login-box">
        <div class="icon-box">
            <img src="jerusalem.png" alt="Logo" style="width: 60px; height: 60px; object-fit: contain;">
        </div>

        <h4 class="fw-bold">Welcome</h4>
        <p class="text-muted small">LunettiStar CV Generator</p>


        <form id="login-screen">


            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" placeholder="Email" name="email" id="email" required>
                </div>
            </div>

            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" placeholder="Password" name="password" id="password" required>
                </div>
            </div>

            <!-- <div class="text-end mb-3">
            <a href="#" class="text-decoration-none">Forgot password?</a>
        </div>-->

            <button type="submit" class="btn btn-black" name="login">Get Started</button>

        </form>

        <p class="mt-3">- Or sign in with -</p>

        <div class="social-icons">
            <a href="#"><i class="fab fa-google"></i></a>
            <a href="#"><i class="fab fa-facebook"></i></a>
            <a href="#"><i class="fab fa-apple"></i></a>
        </div>
    </div>
</body>

<script src="<?= BASE_URL ?>/assets/auth/auth.js"></script>

</html>