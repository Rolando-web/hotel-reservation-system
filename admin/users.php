<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/middleware.php';

adminMiddleware();

// Handle user actions
if (isset($_GET['action']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'toggle') {
        $stmt = $conn->prepare("UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ? AND role = 'user'");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            setFlashMessage('success', 'User status updated');
        }
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            setFlashMessage('success', 'User deleted successfully');
        } else {
            setFlashMessage('error', 'Cannot delete user with existing reservations');
        }
    }
    redirect('/admin/users.php');
}

// Get all users
$users = $conn->query("
    SELECT g.*, 
           COUNT(DISTINCT r.id) as total_bookings,
           SUM(CASE WHEN r.status = 'approved' THEN r.total_price ELSE 0 END) as total_spent
    FROM users g
    LEFT JOIN reservations r ON g.id = r.user_id
    WHERE g.role = 'user'
    GROUP BY g.id
    ORDER BY g.created_at DESC
");

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
    <style>
        body { background:#0f172a; min-height:100vh; }
        .content-wrap { margin-left:0; }
        @media (min-width:768px){ .content-wrap { margin-left:16rem; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="content-wrap max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-slate-100">
        <!-- Flash Message -->
        <?php if ($flash): ?>
            <div class="mb-6 bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-50 border-l-4 border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-500 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-700 p-4 rounded-md">
                <div class="flex items-center">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
                    <span><?php echo $flash['message']; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="mb-8">
            <h1 class="page-heading text-3xl font-bold">Manage Users</h1>
            <p class="text-slate-400 mt-1">View and manage registered users</p>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">User</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Contact</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Total Bookings</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Total Spent</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Joined</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['name']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600">
                                        <?php if ($user['phone']): ?>
                                            <p><i class="fas fa-phone mr-1"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($user['address']): ?>
                                            <p class="text-xs mt-1"><?php echo htmlspecialchars(substr($user['address'], 0, 30)) . '...'; ?></p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-800 font-semibold"><?php echo $user['total_bookings']; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-800 font-semibold"><?php echo formatCurrency($user['total_spent']); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?php echo formatDate($user['created_at']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <button 
                                            onclick="toggleStatus(<?php echo $user['id']; ?>)"
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs transition-colors"
                                            title="Toggle Status"
                                        >
                                            <i class="fas fa-toggle-<?php echo $user['status'] === 'active' ? 'on' : 'off'; ?>"></i>
                                        </button>
                                        <button 
                                            onclick="deleteUser(<?php echo $user['id']; ?>)"
                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs transition-colors"
                                            title="Delete User"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Toggle Modal -->
    <div id="toggleModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Toggle User Status?</h3>
            <p class="text-gray-600 mb-6">Change the status of this user account?</p>
            <div class="flex space-x-3">
                <button 
                    onclick="closeModal('toggleModal')"
                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 rounded-lg transition-colors"
                >
                    Cancel
                </button>
                <form id="toggleForm" method="POST" class="flex-1">
                    <button 
                        type="submit"
                        class="w-full bg-yellow-500 hover:bg-yellow-600 text-white py-2 rounded-lg transition-colors"
                    >
                        Confirm
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Delete User?</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to delete this user? This action cannot be undone.</p>
            <div class="flex space-x-3">
                <button 
                    onclick="closeModal('deleteModal')"
                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 rounded-lg transition-colors"
                >
                    Cancel
                </button>
                <form id="deleteForm" method="POST" class="flex-1">
                    <button 
                        type="submit"
                        class="w-full bg-red-500 hover:bg-red-600 text-white py-2 rounded-lg transition-colors"
                    >
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleStatus(id) {
            document.getElementById('toggleForm').action = '?action=toggle&id=' + id;
            document.getElementById('toggleModal').classList.remove('hidden');
        }

        function deleteUser(id) {
            document.getElementById('deleteForm').action = '?action=delete&id=' + id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
    </script>
</body>
</html>
