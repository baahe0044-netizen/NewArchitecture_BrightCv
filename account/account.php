<?php
/**
 * account.php - Complete Account Management System
 * Features: Register, Login, Profile Management, Settings
 */

/*session_start();*/

// Database connection
$dbname = 'lunettistar_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$message = '';
$messageType = '';
$currentUser = null;

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // REGISTER
    if ($action === 'register') {
        $user = trim($_POST['username']);
        $email = trim($_POST['email']);
        $pass = $_POST['password'];
        $confirmPass = $_POST['confirm_password'];
        
        if (strlen($user) < 3) {
            $message = 'Username must be at least 3 characters';
            $messageType = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email address';
            $messageType = 'error';
        } elseif (strlen($pass) < 6) {
            $message = 'Password must be at least 6 characters';
            $messageType = 'error';
        } elseif ($pass !== $confirmPass) {
            $message = 'Passwords do not match';
            $messageType = 'error';
        } else {
            // Check if username or email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$user, $email]);
            
            if ($stmt->fetch()) {
                $message = 'Username or email already exists';
                $messageType = 'error';
            } else {
                // Create account
                $hashedPass = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                
                if ($stmt->execute([$user, $email, $hashedPass])) {
                    $message = 'Account created successfully! You can now login.';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to create account';
                    $messageType = 'error';
                }
            }
        }
    }
    
    // LOGIN
    elseif ($action === 'login') {
        $identifier = trim($_POST['username']); // Can be username or email
        $pass = $_POST['password'];
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($pass, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $message = 'Login successful! Welcome back, ' . htmlspecialchars($user['username']);
            $messageType = 'success';
            $currentUser = $user;
        } else {
            $message = 'Invalid username/email or password';
            $messageType = 'error';
        }
    }
    
    // LOGOUT
    elseif ($action === 'logout') {
        session_destroy();
        header('Location: account.php');
        exit;
    }
    
    // UPDATE PROFILE
    elseif ($action === 'update_profile' && $currentUser) {
        $newUsername = trim($_POST['username']);
        $newEmail = trim($_POST['email']);
        
        if (strlen($newUsername) < 3) {
            $message = 'Username must be at least 3 characters';
            $messageType = 'error';
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email address';
            $messageType = 'error';
        } else {
            // Check if username/email is taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$newUsername, $newEmail, $currentUser['id']]);
            
            if ($stmt->fetch()) {
                $message = 'Username or email already taken by another user';
                $messageType = 'error';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                if ($stmt->execute([$newUsername, $newEmail, $currentUser['id']])) {
                    $_SESSION['username'] = $newUsername;
                    $message = 'Profile updated successfully!';
                    $messageType = 'success';
                    
                    // Refresh current user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$currentUser['id']]);
                    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $message = 'Failed to update profile';
                    $messageType = 'error';
                }
            }
        }
    }
    
    // CHANGE PASSWORD
    elseif ($action === 'change_password' && $currentUser) {
        $currentPass = $_POST['current_password'];
        $newPass = $_POST['new_password'];
        $confirmPass = $_POST['confirm_new_password'];
        
        if (!password_verify($currentPass, $currentUser['password_hash'])) {
            $message = 'Current password is incorrect';
            $messageType = 'error';
        } elseif (strlen($newPass) < 6) {
            $message = 'New password must be at least 6 characters';
            $messageType = 'error';
        } elseif ($newPass !== $confirmPass) {
            $message = 'New passwords do not match';
            $messageType = 'error';
        } else {
            $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            
            if ($stmt->execute([$hashedPass, $currentUser['id']])) {
                $message = 'Password changed successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to change password';
                $messageType = 'error';
            }
        }
    }
    
    // DELETE ACCOUNT
    elseif ($action === 'delete_account' && $currentUser) {
        $confirmPassword = $_POST['confirm_password'];
        
        if (password_verify($confirmPassword, $currentUser['password_hash'])) {
            // Delete user's resumes first
            $stmt = $pdo->prepare("DELETE FROM resumes WHERE user_id = ?");
            $stmt->execute([$currentUser['id']]);
            
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$currentUser['id']])) {
                session_destroy();
                header('Location: account.php?deleted=1');
                exit;
            }
        } else {
            $message = 'Incorrect password. Account not deleted.';
            $messageType = 'error';
        }
    }
}

// Check for deletion confirmation
if (isset($_GET['deleted'])) {
    $message = 'Your account has been permanently deleted.';
    $messageType = 'info';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $currentUser ? 'Account Settings' : 'Login / Register' ?> - Bright CV</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --navy: #0b1240;
            --primary: #667eea;
            --primary-dark: #5568d3;
            --secondary: #764ba2;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --text: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Content wrapper to work with sidebar */
        .main-content {
            margin-left: 0;
            padding: 24px;
            max-width: 100%;
			 display: flex; 
			 flex-direction: column;
    align-items: center;
        }

        /* Message Alert */
        .alert {
            padding: 16px 24px;
            border-radius: 16px;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 2px solid #3b82f6;
        }

        /* Page Header */
        .page-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 32px;
        }

        .page-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
        }

        .page-title {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Auth Forms (Login/Register) */
        .auth-container {
            max-width: 8000px;
            margin: 40px auto;
			padding: 0 24px; 
        }

        .auth-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 32px;
        }

        .tab-btn {
            flex: 1;
            padding: 16px;
            background: white;
            border: 2px solid var(--border);
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .tab-btn:hover {
            border-color: var(--primary);
        }

        .tab-btn.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-color: transparent;
        }

        .auth-form {
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            display: none;
        }

        .auth-form.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .auth-form h2 {
            font-size: 28px;
            margin-bottom: 24px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Settings Layout */
        .settings-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 32px;
            max-width: 1400px;
        }

        .settings-sidebar {
            background: white;
            padding: 24px;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            height: fit-content;
            position: sticky;
            top: 24px;
        }

        .sidebar-item {
            padding: 14px 18px;
            margin-bottom: 8px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .sidebar-item:hover {
            background: linear-gradient(135deg, #667eea10, #764ba210);
        }

        .sidebar-item.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .settings-content {
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .settings-section {
            display: none;
        }

        .settings-section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .settings-section h2 {
            font-size: 32px;
            margin-bottom: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .settings-section p {
            color: var(--text-light);
            margin-bottom: 32px;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text);
        }

        .form-group input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group .hint {
            font-size: 13px;
            color: var(--text-light);
            margin-top: 6px;
        }

        .btn {
            padding: 14px 32px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .btn-full {
            width: 100%;
        }

        /* User Card */
        .user-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 32px;
            border-radius: 20px;
            margin-bottom: 32px;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            background: white;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .user-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .user-email {
            opacity: 0.9;
            font-size: 14px;
        }

        /* Danger Zone */
        .danger-zone {
            background: #fee2e2;
            border: 2px solid #ef4444;
            padding: 24px;
            border-radius: 16px;
            margin-top: 40px;
        }

        .danger-zone h3 {
            color: #991b1b;
            margin-bottom: 12px;
        }

        .danger-zone p {
            color: #7f1d1d;
            margin-bottom: 20px;
        }

        /* Resume Cards */
        .resume-card {
            background: linear-gradient(135deg, #667eea10, #764ba210);
            padding: 20px;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .resume-card h4 {
            margin-bottom: 4px;
        }

        .resume-card small {
            color: var(--text-light);
        }

        /* Empty State */
        .empty-state {
            color: var(--text-light);
            padding: 60px 40px;
            text-align: center;
            background: #f9fafb;
            border-radius: 16px;
        }

        .empty-state i {
            font-size: 56px;
            margin-bottom: 20px;
            display: block;
            opacity: 0.3;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .settings-layout {
                grid-template-columns: 1fr;
            }

            .settings-sidebar {
                position: static;
            }

            .auth-container {
                margin: 20px;
            }

            .main-content {
                padding: 16px;
            }
        }

        /* Link styles */
        a {
            color: var(--primary);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-icon">
                <i class="fas fa-user-circle"></i>
            </div>
            <h1 class="page-title"><?= $currentUser ? 'Account Settings' : 'Account' ?></h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!$currentUser): ?>
            <!-- LOGIN / REGISTER FORMS -->
            <div class="auth-container">
                <div class="auth-tabs">
                    <button class="tab-btn active" onclick="switchTab('login')">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                    <button class="tab-btn" onclick="switchTab('register')">
                        <i class="fas fa-user-plus"></i> Register
                    </button>
                </div>

                <!-- Login Form -->
                <div class="auth-form active" id="login-form">
                    <h2>Welcome Back!</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="form-group">
                            <label>Username or Email</label>
                            <input type="text" name="username" required>
                        </div>

                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" required>
                        </div>

                        <button type="submit" class="btn btn-full">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </form>
                </div>

                <!-- Register Form -->
                <div class="auth-form" id="register-form">
                    <h2>Create Your Account</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="register">
                        
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" required>
                            <div class="hint">At least 3 characters</div>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" required>
                            <div class="hint">At least 6 characters</div>
                        </div>

                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" required>
                        </div>

                        <button type="submit" class="btn btn-full">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <!-- ACCOUNT SETTINGS -->
            <div class="settings-layout">
                <!-- Sidebar -->
                <div class="settings-sidebar">
                    <div class="user-card">
                        <div class="user-avatar">
                            <?= strtoupper(substr($currentUser['username'], 0, 1)) ?>
                        </div>
                        <div class="user-name"><?= htmlspecialchars($currentUser['username']) ?></div>
                        <div class="user-email"><?= htmlspecialchars($currentUser['email']) ?></div>
                    </div>

                    <div class="sidebar-item active" onclick="showSection('profile')">
                        <i class="fas fa-user"></i> Profile
                    </div>
                    <div class="sidebar-item" onclick="showSection('security')">
                        <i class="fas fa-lock"></i> Security
                    </div>
                    <div class="sidebar-item" onclick="showSection('resumes')">
                        <i class="fas fa-file-alt"></i> My Resumes
                    </div>
                    <div class="sidebar-item" onclick="showSection('danger')">
                        <i class="fas fa-exclamation-triangle"></i> Delete Account
                    </div>

                    <hr style="margin: 20px 0; border: none; border-top: 1px solid var(--border);">

                    <form method="POST" style="margin-top: 16px;">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="btn btn-secondary btn-full">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                </div>

                <!-- Main Content -->
                <div class="settings-content">
                    <!-- Profile Section -->
                    <div class="settings-section active" id="profile-section">
                        <h2>Profile Settings</h2>
                        <p>Update your personal information</p>

                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" value="<?= htmlspecialchars($currentUser['username']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($currentUser['email']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Account Created</label>
                                <input type="text" value="<?= date('F j, Y', strtotime($currentUser['created_at'])) ?>" disabled>
                            </div>

                            <button type="submit" class="btn">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>

                    <!-- Security Section -->
                    <div class="settings-section" id="security-section">
                        <h2>Security Settings</h2>
                        <p>Change your password</p>

                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" required>
                            </div>

                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" required>
                                <div class="hint">At least 6 characters</div>
                            </div>

                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_new_password" required>
                            </div>

                            <button type="submit" class="btn">
                                <i class="fas fa-lock"></i> Change Password
                            </button>
                        </form>
                    </div>

                    <!-- Resumes Section -->
                    <div class="settings-section" id="resumes-section">
                        <h2>My Resumes</h2>
                        <p>Manage your resume documents</p>

                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM resumes WHERE user_id = ? ORDER BY updated_at DESC");
                        $stmt->execute([$currentUser['id']]);
                        $resumes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php if (count($resumes) > 0): ?>
                            <div style="margin-bottom: 24px;">
                                <?php foreach ($resumes as $resume): ?>
                                    <div class="resume-card">
                                        <div>
                                            <h4><?= htmlspecialchars($resume['name']) ?></h4>
                                            <small>Last updated: <?= date('M j, Y', strtotime($resume['updated_at'])) ?></small>
                                        </div>
                                        <a href="builder.php?resume_id=<?= $resume['id'] ?>" class="btn">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-file-alt"></i>
                                <p>You haven't created any resumes yet.</p>
                            </div>
                        <?php endif; ?>

                        <a href="Template.php" class="btn">
                            <i class="fas fa-plus"></i> Create New Resume
                        </a>
                    </div>

                    <!-- Danger Zone Section -->
                    <div class="settings-section" id="danger-section">
                        <h2>Delete Account</h2>
                        <p>Permanently delete your account and all data</p>

                        <div class="danger-zone">
                            <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                            <p>
                                <strong>Warning:</strong> This action cannot be undone. Deleting your account will permanently remove:
                            </p>
                            <ul style="margin-left: 20px; margin-bottom: 20px; color: #7f1d1d;">
                                <li>All your resumes (<?= count($resumes) ?> resume<?= count($resumes) !== 1 ? 's' : '' ?>)</li>
                                <li>Your profile information</li>
                                <li>All account data</li>
                            </ul>

                            <form method="POST" onsubmit="return confirm('Are you absolutely sure? This action cannot be undone!');">
                                <input type="hidden" name="action" value="delete_account">
                                
                                <div class="form-group">
                                    <label style="color: #991b1b;">Confirm your password to delete account</label>
                                    <input type="password" name="confirm_password" required style="border-color: #ef4444;">
                                </div>

                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Permanently Delete Account
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Switch between Login/Register tabs
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.auth-form').forEach(form => form.classList.remove('active'));
            
            if (tab === 'login') {
                document.querySelectorAll('.tab-btn')[0].classList.add('active');
                document.getElementById('login-form').classList.add('active');
            } else {
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
                document.getElementById('register-form').classList.add('active');
            }
        }

        // Show settings section
        function showSection(section) {
            document.querySelectorAll('.sidebar-item').forEach(item => item.classList.remove('active'));
            document.querySelectorAll('.settings-section').forEach(sec => sec.classList.remove('active'));
            
            event.target.closest('.sidebar-item').classList.add('active');
            document.getElementById(section + '-section').classList.add('active');
        }
    </script>
</body>
</html>