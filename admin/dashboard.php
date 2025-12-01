<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/middleware.php';

adminMiddleware();

$stats = [];

$result = $conn->query("SELECT COUNT(*) as total FROM rooms");
$stats['total_rooms'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'available'");
$stats['available_rooms'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$stats['total_users'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM reservations");
$stats['total_reservations'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'pending'");
$stats['pending_reservations'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'approved'");
$stats['approved_reservations'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT SUM(total_price) as revenue FROM reservations WHERE status = 'approved'");
$stats['total_revenue'] = $result->fetch_assoc()['revenue'] ?? 0;


$recent_reservations = $conn->query("
    SELECT r.*, g.name as guest_name, rm.room_number 
    FROM reservations r
    JOIN users g ON r.user_id = g.id
    JOIN rooms rm ON r.room_id = rm.id
    ORDER BY r.created_at DESC
    LIMIT 5
");

// Reservation status distribution
$status_dist = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM reservations 
    GROUP BY status
");

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="page-heading text-3xl font-bold">Admin Dashboard 📊</h1>
            <p class="text-slate-400 mt-1">Overview of hotel operations</p>
        </div>

        <!-- Statistics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Rooms -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Total Rooms</p>
                        <p class="text-3xl font-bold mt-2"><?php echo $stats['total_rooms']; ?></p>
                        <p class="text-blue-100 text-xs mt-1"><?php echo $stats['available_rooms']; ?> available</p>
                    </div>
                    <div class="bg-white bg-opacity-20 p-4 rounded-full">
                        <i class="fas fa-bed text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Users -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">Total Users</p>
                        <p class="text-3xl font-bold mt-2"><?php echo $stats['total_users']; ?></p>
                        <p class="text-purple-100 text-xs mt-1">Registered guests</p>
                    </div>
                    <div class="bg-white bg-opacity-20 p-4 rounded-full">
                        <i class="fas fa-users text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Pending Reservations -->
            <div class="bg-gradient-to-br from-yellow-500 to-orange-500 text-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-100 text-sm font-medium">Pending</p>
                        <p class="text-3xl font-bold mt-2"><?php echo $stats['pending_reservations']; ?></p>
                        <p class="text-yellow-100 text-xs mt-1">Awaiting approval</p>
                    </div>
                    <div class="bg-white bg-opacity-20 p-4 rounded-full">
                        <i class="fas fa-clock text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 text-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium">Total Revenue</p>
                        <p class="text-3xl font-bold mt-2"><?php echo formatCurrency($stats['total_revenue']); ?></p>
                        <p class="text-green-100 text-xs mt-1">From approved bookings</p>
                    </div>
                    <div class="bg-white bg-opacity-20 p-4 rounded-full">
                        <i class="fas fa-dollar-sign text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Reservation Status Chart -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Reservation Status</h2>
                <canvas id="statusChart"></canvas>
            </div>

            <!-- Quick Stats -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Quick Stats</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="bg-blue-500 p-3 rounded-full mr-4">
                                <i class="fas fa-calendar-check text-white"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Total Bookings</p>
                                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total_reservations']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="bg-green-500 p-3 rounded-full mr-4">
                                <i class="fas fa-check-circle text-white"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Approved</p>
                                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['approved_reservations']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-yellow-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="bg-yellow-500 p-3 rounded-full mr-4">
                                <i class="fas fa-hourglass-half text-white"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Pending</p>
                                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['pending_reservations']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Reservations -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Recent Reservations</h2>
                <a href="reservations.php" class="text-indigo-600 hover:text-indigo-700 font-medium">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Guest</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Room</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Check-in</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Check-out</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($reservation = $recent_reservations->fetch_assoc()): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm text-gray-800"><?php echo htmlspecialchars($reservation['guest_name']); ?></td>
                                <td class="px-4 py-4 text-sm text-gray-800">Room <?php echo htmlspecialchars($reservation['room_number']); ?></td>
                                <td class="px-4 py-4 text-sm text-gray-600"><?php echo formatDate($reservation['check_in_date']); ?></td>
                                <td class="px-4 py-4 text-sm text-gray-600"><?php echo formatDate($reservation['check_out_date']); ?></td>
                                <td class="px-4 py-4 text-sm font-semibold text-gray-800"><?php echo formatCurrency($reservation['total_price']); ?></td>
                                <td class="px-4 py-4">
                                    <?php
                                    $badge_colors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        'cancelled' => 'bg-gray-100 text-gray-800'
                                    ];
                                    $badge_class = $badge_colors[$reservation['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($reservation['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Status Chart
        const statusData = {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#FCD34D', // Yellow for pending
                    '#34D399', // Green for approved
                    '#F87171', // Red for rejected
                    '#9CA3AF'  // Gray for cancelled
                ]
            }]
        };

        <?php 
        $status_dist->data_seek(0);
        while ($row = $status_dist->fetch_assoc()): 
        ?>
        statusData.labels.push('<?php echo ucfirst($row['status']); ?>');
        statusData.datasets[0].data.push(<?php echo $row['count']; ?>);
        <?php endwhile; ?>

        const statusChart = new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: statusData,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
