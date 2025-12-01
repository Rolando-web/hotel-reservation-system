<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/middleware.php';

userMiddleware();

$user = getCurrentUser();

// Get user statistics
$user_id = $_SESSION['user_id'];

// Total reservations
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM reservations WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_reservations = $stmt->get_result()->fetch_assoc()['total'];

// Pending reservations
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM reservations WHERE user_id = ? AND status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_reservations = $stmt->get_result()->fetch_assoc()['total'];

// Approved reservations
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM reservations WHERE user_id = ? AND status = 'approved'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$approved_reservations = $stmt->get_result()->fetch_assoc()['total'];

// Recent reservations
$stmt = $conn->prepare("
    SELECT r.*, rm.room_number, rm.room_type, rm.image 
    FROM reservations r
    JOIN rooms rm ON r.room_id = rm.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_reservations = $stmt->get_result();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
    <style>
        body {
            background-image: url('https://images.unsplash.com/photo-1631049307264-da0ec9d70304?q=80&w=2070');
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

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Flash Message -->
        <?php if ($flash): ?>
            <div class="mb-6 bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-50 border-l-4 border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-500 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-700 p-4 rounded-md animate-fade-in">
                <div class="flex items-center">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
                    <span><?php echo $flash['message']; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white">Welcome back, <?php echo htmlspecialchars($user['name']); ?>! 👋</h1>
            <p class="text-gray-300 mt-1">Here's your booking overview</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Reservations -->
            <div class="bg-white rounded-xl shadow-md p-6 transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total Bookings</p>
                        <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $total_reservations; ?></p>
                    </div>
                    <div class="bg-blue-100 p-4 rounded-full">
                        <i class="fas fa-calendar-check text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Pending Reservations -->
            <div class="bg-white rounded-xl shadow-md p-6 transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Pending</p>
                        <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $pending_reservations; ?></p>
                    </div>
                    <div class="bg-yellow-100 p-4 rounded-full">
                        <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Approved Reservations -->
            <div class="bg-white rounded-xl shadow-md p-6 transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Approved</p>
                        <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $approved_reservations; ?></p>
                    </div>
                    <div class="bg-green-100 p-4 rounded-full">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <a href="rooms.php" class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition-all">
                <div class="flex items-center">
                    <div class="bg-white bg-opacity-20 p-4 rounded-full mr-4">
                        <i class="fas fa-bed text-3xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">Browse Rooms</h3>
                        <p class="text-indigo-100">Find your perfect stay</p>
                    </div>
                </div>
            </a>

            <a href="reservations.php" class="bg-gradient-to-r from-pink-500 to-rose-600 text-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition-all">
                <div class="flex items-center">
                    <div class="bg-white bg-opacity-20 p-4 rounded-full mr-4">
                        <i class="fas fa-list text-3xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">My Reservations</h3>
                        <p class="text-pink-100">View booking history</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Recent Reservations -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Recent Bookings</h2>
                <a href="reservations.php" class="text-indigo-600 hover:text-indigo-700 font-medium">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

            <?php if ($recent_reservations->num_rows > 0): ?>
                <div class="space-y-4">
                    <?php while ($reservation = $recent_reservations->fetch_assoc()): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-all">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-4">
                                    <?php 
                                        // Handle both local paths and external URLs
                                        $imageSrc = $reservation['image'];
                                        if (!empty($imageSrc) && !filter_var($imageSrc, FILTER_VALIDATE_URL)) {
                                            // Local path - ensure it starts from root
                                            $imageSrc = '..' . $imageSrc;
                                        }
                                    ?>
                                    <img 
                                        src="<?php echo htmlspecialchars($imageSrc); ?>" 
                                        alt="Room <?php echo htmlspecialchars($reservation['room_number']); ?>" 
                                        class="w-20 h-20 rounded-lg object-cover"
                                        onerror="this.src='https://via.placeholder.com/80x80?text=Room+<?php echo $reservation['room_number']; ?>'"
                                    >
                                    <div>
                                        <h3 class="font-semibold text-gray-800">Room <?php echo htmlspecialchars($reservation['room_number']); ?> - <?php echo ucfirst($reservation['room_type']); ?></h3>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <i class="fas fa-calendar mr-1"></i>
                                            <?php echo formatDate($reservation['check_in_date']); ?> - <?php echo formatDate($reservation['check_out_date']); ?>
                                        </p>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <i class="fas fa-users mr-1"></i>
                                            <?php echo $reservation['number_of_guests']; ?> Guest<?php echo $reservation['number_of_guests'] > 1 ? 's' : ''; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <?php
                                    $badge_colors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        'cancelled' => 'bg-gray-100 text-gray-800',
                                        'completed' => 'bg-blue-100 text-blue-800'
                                    ];
                                    $badge_class = $badge_colors[$reservation['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($reservation['status']); ?>
                                    </span>
                                    <p class="text-lg font-bold text-gray-800 mt-2">
                                        <?php echo formatCurrency($reservation['total_price']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-calendar-times text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg">No reservations yet</p>
                    <a href="rooms.php" class="mt-4 inline-block bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                        Browse Rooms
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fade-in 0.5s ease-out;
        }
    </style>
</body>
</html>
