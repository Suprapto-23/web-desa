<?php
// login.php - Simplified Login page for Desa Profesional
session_start();
require_once './config/database.php';
require_once './config/functions.php';

// Basic login validation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $error = '';

    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login_success'] = true;
        header("Location: ./admin/dashboard.php");
        exit;
    } else {
        $error = "Username atau password salah!";
        $_SESSION['login_error'] = $error;
        $_SESSION['login_attempt'] = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin Desa Winduaji</title>
    <link rel="shortcut icon" href="./assets/images/logo.png" type="image/x-icon">
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #4361ee; /* Modern blue */
            --primary-dark: #3a56d4;
            --primary-light: #eef2ff;
            --light: #f8fafc;
            --gray: #64748b;
            --light-gray: #e2e8f0;
            --danger: #ef4444;
            --success: #22c55e;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary-light) 0%, #f0f9ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            max-width: 440px;
            width: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 40px 35px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .login-logo {
            width: 70px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .login-logo:hover {
            transform: scale(1.05);
        }

        .login-title {
            font-size: 1.9rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 8px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-subtitle {
            font-size: 0.95rem;
            color: var(--gray);
            line-height: 1.5;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-dark);
            font-size: 0.95rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .form-label i {
            margin-right: 8px;
            font-size: 0.9rem;
        }

        .form-control {
            height: 50px;
            border-radius: 10px;
            border: 2px solid var(--light-gray);
            padding: 10px 16px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.15);
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .btn-login {
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            height: 50px;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
            box-shadow: 0 4px 6px rgba(67, 97, 238, 0.2);
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .forgot-password {
            display: block;
            text-align: right;
            color: var(--primary);
            font-size: 0.9rem;
            text-decoration: none;
            margin-top: 15px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .forgot-password:hover {
            color: var(--primary-dark);
            text-decoration: underline;
            transform: translateX(-5px);
        }

        .alert {
            border-radius: 10px;
            padding: 14px;
            font-size: 0.9rem;
            margin-bottom: 25px;
            border: none;
            background-color: #fef2f2;
            color: var(--danger);
        }

        .alert i {
            margin-right: 8px;
        }

        .copyright {
            text-align: center;
            margin-top: 30px;
            color: var(--gray);
            font-size: 0.85rem;
            padding-top: 20px;
            border-top: 1px solid var(--light-gray);
        }

        .language-switcher {
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .language-switcher .form-select {
            border-radius: 20px;
            padding: 6px 12px 6px 36px;
            font-size: 0.85rem;
            border: 2px solid var(--light-gray);
            background: white;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%234361ee' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 12px center;
            background-size: 16px;
        }

        /* Password toggle */
        .password-input-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray);
            z-index: 5;
            background: white;
            padding: 4px;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .password-toggle:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        /* Back button */
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            color: var(--gray);
            font-size: 1.3rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .back-button:hover {
            color: var(--primary);
            background: var(--primary-light);
            transform: translateX(-3px);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }

        .login-container {
            animation: fadeIn 0.6s ease-out;
        }

        .btn-login {
            animation: pulse 2s infinite;
        }

        .btn-login:hover {
            animation: none;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .login-container {
                padding: 30px 25px;
            }

            .login-title {
                font-size: 1.7rem;
            }

            .login-subtitle {
                font-size: 0.9rem;
            }
            
            .back-button, .language-switcher {
                position: relative;
                top: unset;
                left: unset;
                right: unset;
                margin-bottom: 15px;
            }
            
            .language-switcher {
                display: flex;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <a href="landingpage.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <div class="language-switcher">
            <select class="form-select">
                <option selected>ID - Indonesia</option>
                <option value="1">EN - English</option>
            </select>
        </div>

        <div class="login-header">
            <img src="./assets/images/logo.png" alt="Desa Profesional Logo" class="login-logo">
            <h2 class="login-title">Masuk ke Akun</h2>
            <p class="login-subtitle">Sistem Administrasi Desa Winduaji</p>
        </div>

        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="loginForm">
            <div class="mb-4">
                <label for="username" class="form-label">
                    <i class="fas fa-user"></i>Nama Pengguna
                </label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required autocomplete="username">
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i>Kata Sandi
                </label>
                <div class="password-input-group">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                </div>
            </div>
            <button type="submit" class="btn btn-login btn-primary">
                <i class="fas fa-sign-in-alt me-2"></i>Masuk ke Sistem
            </button>
        </form>

        <div class="copyright">
            &copy; <?php echo date('Y'); ?> Desa Winduaji - Paninggaran<br>
            Hak Cipta Dilindungi
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    
    <script>
        // Password toggle functionality
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        
        togglePassword.addEventListener('click', function() {
            // Toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle the icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Input focus animation
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.background = 'var(--primary-light)';
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.style.background = 'white';
                this.parentElement.classList.remove('focused');
            });
        });

        // SweetAlert for login status
        <?php if (isset($_SESSION['login_attempt'])): ?>
            <?php unset($_SESSION['login_attempt']); ?>
            // Reset form fields
            document.getElementById('loginForm').reset();
            
            // Show error alert
            Swal.fire({
                icon: 'error',
                title: 'Login Gagal',
                text: '<?php echo addslashes($error); ?>',
                confirmButtonColor: '#4361ee',
                confirmButtonText: 'Coba Lagi',
                background: '#fff',
                iconColor: '#ef4444',
                timer: 5000,
                timerProgressBar: true,
            });
        <?php endif; ?>

        // Form submission animation
        const loginForm = document.getElementById('loginForm');
        loginForm.addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            btn.disabled = true;
        });
    </script>
</body>
</html>