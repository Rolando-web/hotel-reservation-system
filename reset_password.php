<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/middleware.php';

guestMiddleware();

if (!isset($_SESSION['reset_user_id'])) {
    setFlashMessage('error', 'Password reset not initialized.');
    redirect('/forgot_password.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p1 = $_POST['password'] ?? '';
    $p2 = $_POST['password_confirm'] ?? '';
    if (strlen($p1) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($p1 !== $p2) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($p1, PASSWORD_BCRYPT);
        $uid = (int)$_SESSION['reset_user_id'];
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $uid);
        if ($stmt->execute()) {
            unset($_SESSION['reset_user_id']);
            setFlashMessage('success', 'Password updated. Please sign in.');
            redirect('/login.php');
        } else {
            $error = 'Failed to update password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Password - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <style>
        body {
            background-image: url('https://images.unsplash.com/photo-1584132967334-10e028bd69f7?q=80&w=2070');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.65);
            z-index: -1;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2 text-center">Create New Password</h1>
        <p class="text-gray-600 text-center mb-6">Choose a strong password for your account.</p>

        <?php if ($error): ?>
            <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-md">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" name="password" required minlength="6" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="••••••••">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input type="password" name="password_confirm" required minlength="6" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="••••••••">
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg font-semibold">
                Update Password
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="login.php" class="text-gray-600 hover:text-gray-800"><i class="fas fa-arrow-left mr-1"></i> Back to login</a>
        </div>
    </div>
</body>
</html>
