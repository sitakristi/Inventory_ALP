<?php
session_start();

if (isset($_SESSION['user_email'])) {
    header('Location: dashboard.php');
    exit();
}

include 'firebase_config.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            $signInResult = $auth->signInWithEmailAndPassword($email, $password);
            
            $_SESSION['user_email'] = $email;
            $_SESSION['user_uid'] = $signInResult->firebaseUserId();
            
            header('Location: dashboard.php');
            exit();
        } catch (\Exception $e) {
            $error_message = 'Login gagal: Email atau password salah.';
        }
    } else {
        $error_message = 'Harap isi email dan password.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Inventaris</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        // mengecek tema sebelum body dirender
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
</head>
<body>
    <!-- Tombol Dark/Light Mode di Kanan Atas -->
    <button type="button" id="theme-toggle" class="theme-toggle-btn" title="Ubah Tema">
        <i class="fa-solid fa-moon" id="theme-icon"></i>
    </button>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fa-solid fa-box-open login-logo"></i>
                <h2>Inventaris Cloud</h2>
                <p>Silakan masuk ke akun Anda</p>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" id="email" name="email" required placeholder="admin@domain.com">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="password" name="password" required placeholder="Masukkan password">
                    </div>
                </div>

                <button type="submit" class="btn-login">Masuk</button>
            </form>
        </div>
    </div>

    <script>
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');

        // Sesuaikan ikon saat halaman pertama kali dibuka
        if (document.documentElement.getAttribute('data-theme') === 'dark') {
            themeIcon.classList.replace('fa-moon', 'fa-sun');
        }

        // Event klik untuk mengubah tema dan menyimpannya ke localStorage
        themeToggleBtn.addEventListener('click', () => {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            
            if (isDark) {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                themeIcon.classList.replace('fa-sun', 'fa-moon');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                themeIcon.classList.replace('fa-moon', 'fa-sun');
            }
        });
    </script>
</body>
</html>