<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/middleware.php';

adminMiddleware();

// Handle reservation actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $reservation_id = (int)$_POST['reservation_id'];
    $action = $_POST['action'];
    $admin_notes = sanitize($_POST['admin_notes'] ?? '');
    
    // fetch user_id for notifications
    $uid = null;
    $stmtUid = $conn->prepare("SELECT user_id FROM reservations WHERE id = ?");
    $stmtUid->bind_param("i", $reservation_id);
    $stmtUid->execute();
    $resUid = $stmtUid->get_result();
    if ($rowUid = $resUid->fetch_assoc()) {
        $uid = (int)$rowUid['user_id'];
    }
    
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE reservations SET status = 'approved', admin_notes = ? WHERE id = ?");
        $stmt->bind_param("si", $admin_notes, $reservation_id);
        if ($stmt->execute()) {
            setFlashMessage('success', 'Reservation approved successfully');
            if ($uid) {
                $msg = 'Your reservation #' . $reservation_id . ' has been approved.';
                $link = APP_URL . '/user/reservations.php';
                $nstmt = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'reservation', ?, ?)");
                $nstmt->bind_param("iss", $uid, $msg, $link);
                $nstmt->execute();
            }
        }
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE reservations SET status = 'rejected', admin_notes = ? WHERE id = ?");
        $stmt->bind_param("si", $admin_notes, $reservation_id);
        if ($stmt->execute()) {
            setFlashMessage('success', 'Reservation rejected');
            if ($uid) {
                $msg = 'Your reservation #' . $reservation_id . ' has been rejected.';
                if (!empty($admin_notes)) {
                    $msg .= ' Note: ' . $admin_notes;
                }
                $link = APP_URL . '/user/reservations.php';
                $nstmt = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'reservation', ?, ?)");
                $nstmt->bind_param("iss", $uid, $msg, $link);
                $nstmt->execute();
            }
        }
    }
    redirect('/admin/reservations.php');
}

// Get filter
$filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';

// Build query
$query = "
    SELECT r.*, g.name as guest_name, g.email as guest_email, g.phone as guest_phone,
           rm.room_number, rm.room_type, rm.image
    FROM reservations r
    JOIN users g ON r.user_id = g.id
    JOIN rooms rm ON r.room_id = rm.id
";

if ($filter !== 'all') {
    $query .= " WHERE r.status = ?";
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($query);
if ($filter !== 'all') {
    $stmt->bind_param("s", $filter);
}
$stmt->execute();
$reservations = $stmt->get_result();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations - <?php echo APP_NAME; ?></title>
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
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="page-heading text-3xl font-bold">Manage Reservations</h1>
                <p class="text-slate-400 mt-1">Review and manage booking requests</p>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="bg-white rounded-xl shadow-md mb-6">
            <div class="flex flex-wrap border-b border-gray-200">
                <a href="?status=all" class="px-6 py-4 text-sm font-medium <?php echo $filter === 'all' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-600 hover:text-gray-800'; ?>">
                    All Reservations
                </a>
                <a href="?status=pending" class="px-6 py-4 text-sm font-medium <?php echo $filter === 'pending' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-600 hover:text-gray-800'; ?>">
                    Pending
                </a>
                <a href="?status=approved" class="px-6 py-4 text-sm font-medium <?php echo $filter === 'approved' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-600 hover:text-gray-800'; ?>">
                    Approved
                </a>
                <a href="?status=rejected" class="px-6 py-4 text-sm font-medium <?php echo $filter === 'rejected' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-600 hover:text-gray-800'; ?>">
                    Rejected
                </a>
                <a href="?status=cancelled" class="px-6 py-4 text-sm font-medium <?php echo $filter === 'cancelled' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-600 hover:text-gray-800'; ?>">
                    Cancelled
                </a>
            </div>
        </div>

        <!-- Reservations List -->
        <?php if ($reservations->num_rows > 0): ?>
            <div class="space-y-6">
                <?php while ($reservation = $reservations->fetch_assoc()): ?>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="p-6">
                            <div class="flex flex-col lg:flex-row gap-6">
                                <!-- Room Image & Info -->
                                <div class="flex items-start space-x-4 flex-1">
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
                                        class="w-24 h-24 rounded-lg object-cover"
                                        onerror="this.src='https://via.placeholder.com/100x100?text=Room+<?php echo $reservation['room_number']; ?>'"
                                    >
                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold text-gray-800 mb-2">
                                            Room <?php echo htmlspecialchars($reservation['room_number']); ?> - <?php echo ucfirst($reservation['room_type']); ?>
                                        </h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                            <div>
                                                <p class="text-gray-600">
                                                    <i class="fas fa-user text-indigo-600 mr-2"></i>
                                                    <?php echo htmlspecialchars($reservation['guest_name']); ?>
                                                </p>
                                                <p class="text-gray-600">
                                                    <i class="fas fa-envelope text-indigo-600 mr-2"></i>
                                                    <?php echo htmlspecialchars($reservation['guest_email']); ?>
                                                </p>
                                                <?php if ($reservation['guest_phone']): ?>
                                                    <p class="text-gray-600">
                                                        <i class="fas fa-phone text-indigo-600 mr-2"></i>
                                                        <?php echo htmlspecialchars($reservation['guest_phone']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <p class="text-gray-600">
                                                    <i class="fas fa-calendar-check text-indigo-600 mr-2"></i>
                                                    Check-in: <?php echo formatDate($reservation['check_in_date']); ?>
                                                </p>
                                                <p class="text-gray-600">
                                                    <i class="fas fa-calendar-times text-indigo-600 mr-2"></i>
                                                    Check-out: <?php echo formatDate($reservation['check_out_date']); ?>
                                                </p>
                                                <p class="text-gray-600">
                                                    <i class="fas fa-users text-indigo-600 mr-2"></i>
                                                    <?php echo $reservation['number_of_guests']; ?> Guest(s)
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status & Actions -->
                                <div class="lg:w-64 text-left lg:text-right">
                                    <?php
                                    $badge_colors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        'cancelled' => 'bg-gray-100 text-gray-800'
                                    ];
                                    $badge_class = $badge_colors[$reservation['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold <?php echo $badge_class; ?> mb-3">
                                        <?php echo ucfirst($reservation['status']); ?>
                                    </span>
                                    <p class="text-2xl font-bold text-gray-800 mb-2">
                                        <?php echo formatCurrency($reservation['total_price']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mb-4">
                                        <?php echo calculateNights($reservation['check_in_date'], $reservation['check_out_date']); ?> night(s)
                                    </p>

                                    <?php if ($reservation['status'] === 'pending'): ?>
                                        <div class="space-y-2">
                                            <button 
                                                onclick="openModal(<?php echo $reservation['id']; ?>, 'approve')"
                                                class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm transition-colors"
                                            >
                                                <i class="fas fa-check mr-1"></i> Approve
                                            </button>
                                            <button 
                                                onclick="openModal(<?php echo $reservation['id']; ?>, 'reject')"
                                                class="w-full bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm transition-colors"
                                            >
                                                <i class="fas fa-times mr-1"></i> Reject
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($reservation['admin_notes']): ?>
                                <div class="mt-4 bg-blue-50 border-l-4 border-blue-500 p-3 rounded">
                                    <p class="text-sm font-semibold text-blue-800">Admin Note:</p>
                                    <p class="text-sm text-blue-700 mt-1"><?php echo htmlspecialchars($reservation['admin_notes']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-16 bg-white rounded-xl shadow-md">
                <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                <p class="text-gray-500 text-xl">No reservations found</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Action Modal -->
    <div id="actionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
            <h3 id="modalTitle" class="text-xl font-bold text-gray-800 mb-4"></h3>
            <form method="POST" action="">
                <input type="hidden" name="reservation_id" id="reservationId">
                <input type="hidden" name="action" id="actionType">
                
                <div class="mb-4">
                    <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Notes (Optional)
                    </label>
                    <textarea 
                        name="admin_notes" 
                        id="admin_notes"
                        rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        placeholder="Add a note for the guest..."
                    ></textarea>
                </div>

                <div class="flex space-x-3">
                    <button 
                        type="button"
                        onclick="closeModal()"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 rounded-lg transition-colors"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        id="submitBtn"
                        class="flex-1 py-2 rounded-lg transition-colors text-white"
                    >
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id, action) {
            document.getElementById('reservationId').value = id;
            document.getElementById('actionType').value = action;
            
            const modal = document.getElementById('actionModal');
            const title = document.getElementById('modalTitle');
            const btn = document.getElementById('submitBtn');
            
            if (action === 'approve') {
                title.textContent = 'Approve Reservation';
                btn.className = 'flex-1 bg-green-500 hover:bg-green-600 py-2 rounded-lg transition-colors text-white';
            } else {
                title.textContent = 'Reject Reservation';
                btn.className = 'flex-1 bg-red-500 hover:bg-red-600 py-2 rounded-lg transition-colors text-white';
            }
            
            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('actionModal').classList.add('hidden');
            document.getElementById('admin_notes').value = '';
        }
    </script>
</body>
</html>
