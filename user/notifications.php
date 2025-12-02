<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/middleware.php';

userMiddleware();

$user_id = (int)$_SESSION['user_id'];

// Mark all as read
if (isset($_GET['mark']) && $_GET['mark'] === 'all' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    setFlashMessage('success', 'All notifications marked as read.');
    header('Location: notifications.php');
    exit;
}

$stmt = $conn->prepare("SELECT id, type, message, link, read_at, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-image: url('https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?q=80&w=2070');
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
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($flash): ?>
            <div class="mb-6 bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-50 border-l-4 border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-500 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-700 p-4 rounded-md">
                <div class="flex items-center">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
                    <span><?php echo $flash['message']; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-white">Notifications</h1>
                <p class="text-gray-300 mt-1">Updates about your reservations</p>
            </div>
            <form method="POST" action="?mark=all">
                <button class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-check-double mr-1"></i> Mark all as read
                </button>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <?php if ($notifications->num_rows === 0): ?>
                <div class="p-8 text-center text-gray-500">No notifications yet.</div>
            <?php else: ?>
                <ul class="divide-y divide-gray-100">
                    <?php while ($n = $notifications->fetch_assoc()): ?>
                        <li class="p-4 flex items-start <?php echo $n['read_at'] ? '' : 'bg-indigo-50'; ?>">
                            <div class="mr-3 mt-1">
                                <i class="fas fa-bell text-indigo-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-800 <?php echo $n['read_at'] ? '' : 'font-semibold'; ?>"><?php echo htmlspecialchars($n['message']); ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo date('M d, Y h:i A', strtotime($n['created_at'])); ?></p>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
