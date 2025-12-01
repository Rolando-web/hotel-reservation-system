<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/middleware.php';

userMiddleware();

$user = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate basic info
    if (empty($name)) {
        $error = 'Name is required';
    } else {
        // Update basic info
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $phone, $address, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $name;
            
            // Handle password change if provided
            if (!empty($current_password) || !empty($new_password)) {
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    $error = 'Please fill all password fields to change password';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match';
                } elseif (strlen($new_password) < 6) {
                    $error = 'Password must be at least 6 characters';
                } else {
                    // Verify current password
                    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    
                    if (password_verify($current_password, $result['password'])) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->bind_param("si", $hashed_password, $user_id);
                        $stmt->execute();
                        $success = 'Profile and password updated successfully!';
                    } else {
                        $error = 'Current password is incorrect';
                    }
                }
            } else {
                $success = 'Profile updated successfully!';
            }
            
            // Refresh user data
            $user = getCurrentUser();
        } else {
            $error = 'Failed to update profile';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
    <style>
        body {
            background-image: url('https://images.unsplash.com/photo-1590490360182-c33d57733427?q=80&w=2070');
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
<body style="min-height: 100vh;">
    <?php include __DIR__ . '/../includes/user_nav.php'; ?>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Profile</h1>
            <p class="text-gray-600 mt-1">Manage your account information</p>
        </div>

        <div class="bg-white rounded-xl shadow-md p-8">
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span><?php echo $success; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <!-- Personal Information Section -->
                <div>
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Personal Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user mr-1"></i> Full Name *
                            </label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                required
                                value="<?php echo htmlspecialchars($user['name']); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-1"></i> Email Address
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                value="<?php echo htmlspecialchars($user['email']); ?>"
                                disabled
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed"
                            >
                            <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-phone mr-1"></i> Phone Number
                        </label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone"
                            value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                    </div>

                    <div class="mt-5">
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-map-marker-alt mr-1"></i> Address
                        </label>
                        <textarea 
                            id="address" 
                            name="address"
                            rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        ><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Password Change Section -->
                <div class="border-t border-gray-200 pt-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Change Password</h2>
                    <p class="text-sm text-gray-600 mb-4">Leave blank if you don't want to change your password</p>
                    
                    <div class="space-y-5">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-lock mr-1"></i> Current Password
                            </label>
                            <input 
                                type="password" 
                                id="current_password" 
                                name="current_password"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-1"></i> New Password
                                </label>
                                <input 
                                    type="password" 
                                    id="new_password" 
                                    name="new_password"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                            </div>

                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-1"></i> Confirm New Password
                                </label>
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end space-x-3 pt-6">
                    <a 
                        href="dashboard.php"
                        class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        Cancel
                    </a>
                    <button 
                        type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-lg font-semibold transition-all transform hover:scale-[1.02] shadow-lg hover:shadow-xl"
                    >
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
