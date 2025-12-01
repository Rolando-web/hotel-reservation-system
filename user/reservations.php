<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/middleware.php';

userMiddleware();

$user_id = $_SESSION['user_id'];

$hasPaymentReference = false;
$colCheck = $conn->query("SHOW COLUMNS FROM reservations LIKE 'payment_reference'");
if ($colCheck && $colCheck->num_rows > 0) {
    $hasPaymentReference = true;
}

if (isset($_GET['cancel']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = (int)$_GET['cancel'];
    $stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $reservation_id, $user_id);
    if ($stmt->execute()) {
        setFlashMessage('success', 'Reservation cancelled successfully');
    } else {
        setFlashMessage('error', 'Failed to cancel reservation');
    }
    redirect('/user/reservations.php');
}

if (isset($_GET['pay']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = (int)$_GET['pay'];
    $payment_method = sanitize($_POST['payment_method']);
    $payment_reference = isset($_POST['payment_reference']) ? sanitize($_POST['payment_reference']) : NULL;
    
    if (($payment_method === 'gcash' || $payment_method === 'paymaya') && empty($payment_reference)) {
        setFlashMessage('error', 'Reference number is required for ' . strtoupper($payment_method) . ' payment');
        redirect('/user/reservations.php');
    }
    
    if ($hasPaymentReference) {
        $stmt = $conn->prepare("UPDATE reservations SET status = 'completed', payment_status = 'paid', payment_method = ?, payment_reference = ?, payment_date = NOW() WHERE id = ? AND user_id = ? AND status = 'approved'");
        $stmt->bind_param("ssii", $payment_method, $payment_reference, $reservation_id, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE reservations SET status = 'completed', payment_status = 'paid', payment_method = ?, payment_date = NOW() WHERE id = ? AND user_id = ? AND status = 'approved'");
        $stmt->bind_param("sii", $payment_method, $reservation_id, $user_id);
    }
    if ($stmt->execute()) {
        $notifTable = $conn->query("SHOW TABLES LIKE 'notifications'");
        if ($notifTable && $notifTable->num_rows > 0) {
            $msg = 'Payment successful for reservation #' . str_pad($reservation_id, 6, '0', STR_PAD_LEFT) . ' via ' . strtoupper($payment_method) . '. Your stay is now complete!';
            $link = '/user/receipt.php?id=' . $reservation_id;
            $nStmt = $conn->prepare("INSERT INTO notifications (user_id, message, link, created_at) VALUES (?, ?, ?, NOW())");
            $nStmt->bind_param("iss", $user_id, $msg, $link);
            $nStmt->execute();
        }

        setFlashMessage('success', 'Payment recorded successfully! Your reservation is now complete.');
        redirect('/user/reservations.php');
    }
}

if (isset($_GET['return']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = (int)$_GET['return'];
    $feedback = isset($_POST['feedback']) ? sanitize($_POST['feedback']) : '';
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    
    $roomStmt = $conn->prepare("SELECT room_id FROM reservations WHERE id = ? AND user_id = ?");
    $roomStmt->bind_param("ii", $reservation_id, $user_id);
    $roomStmt->execute();
    $roomResult = $roomStmt->get_result()->fetch_assoc();
    $room_id = $roomResult ? $roomResult['room_id'] : null;
    
    $stmt = $conn->prepare("UPDATE reservations SET checkout_date = NOW() WHERE id = ? AND user_id = ? AND status = 'approved'");
    $stmt->bind_param("ii", $reservation_id, $user_id);
    if ($stmt->execute()) {
        if (!empty($feedback) && $rating > 0 && $room_id) {
            $reviewsTable = $conn->query("SHOW TABLES LIKE 'reviews'");
            if ($reviewsTable && $reviewsTable->num_rows > 0) {
                $feedbackStmt = $conn->prepare("INSERT INTO reviews (reservation_id, user_id, room_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $feedbackStmt->bind_param("iiiis", $reservation_id, $user_id, $room_id, $rating, $feedback);
                $feedbackStmt->execute();
            }
        }
        
        $notifTable = $conn->query("SHOW TABLES LIKE 'notifications'");
        if ($notifTable && $notifTable->num_rows > 0) {
            $msg = 'Thank you for checking out! Please proceed with payment for reservation #' . str_pad($reservation_id, 6, '0', STR_PAD_LEFT) . '.';
            $link = '/user/reservations.php';
            $nStmt = $conn->prepare("INSERT INTO notifications (user_id, message, link, created_at) VALUES (?, ?, ?, NOW())");
            $nStmt->bind_param("iss", $user_id, $msg, $link);
            $nStmt->execute();
        }
        setFlashMessage('success', 'Checkout successful! Please proceed with payment.');
    } else {
        setFlashMessage('error', 'Failed to complete checkout.');
    }
    redirect('/user/reservations.php');
}

$stmt = $conn->prepare("
    SELECT r.*, rm.room_number, rm.room_type, rm.image, rm.price_per_night
    FROM reservations r
    JOIN rooms rm ON r.room_id = rm.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reservations = $stmt->get_result();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
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
<body style="min-height: 100vh;">
    <?php include __DIR__ . '/../includes/user_nav.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($flash): ?>
            <div class="mb-6 bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-50 border-l-4 border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-500 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-700 p-4 rounded-md">
                <div class="flex items-center">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
                    <span><?php echo $flash['message']; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white">My Reservations</h1>
            <p class="text-gray-300 mt-1">View and manage your bookings</p>
        </div>

        <?php if ($reservations->num_rows > 0): ?>
            <div class="space-y-6">
                <?php while ($reservation = $reservations->fetch_assoc()): ?>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-all">
                        <div class="p-6">
                            <div class="flex flex-col lg:flex-row lg:items-center justify-between">
                                <div class="flex items-start space-x-4 mb-4 lg:mb-0">
                                    <?php 
                                        $imageSrc = $reservation['image'];
                                        if (!empty($imageSrc) && !filter_var($imageSrc, FILTER_VALIDATE_URL)) {
                                            $imageSrc = '..' . $imageSrc;
                                        }
                                    ?>
                                    <img 
                                        src="<?php echo htmlspecialchars($imageSrc); ?>" 
                                        alt="Room <?php echo htmlspecialchars($reservation['room_number']); ?>"
                                        class="w-24 h-24 rounded-lg object-cover"
                                        onerror="this.src='https://via.placeholder.com/100x100?text=Room+<?php echo $reservation['room_number']; ?>'"
                                    >
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-800">
                                            Room <?php echo htmlspecialchars($reservation['room_number']); ?> - <?php echo ucfirst($reservation['room_type']); ?>
                                        </h3>
                                        <div class="mt-2 space-y-1 text-sm text-gray-600">
                                            <p>
                                                <i class="fas fa-calendar-check mr-2 text-indigo-600"></i>
                                                Check-in: <?php echo formatDate($reservation['check_in_date']); ?>
                                            </p>
                                            <p>
                                                <i class="fas fa-calendar-times mr-2 text-indigo-600"></i>
                                                Check-out: <?php echo formatDate($reservation['check_out_date']); ?>
                                            </p>
                                            <p>
                                                <i class="fas fa-users mr-2 text-indigo-600"></i>
                                                <?php echo $reservation['number_of_guests']; ?> Guest<?php echo $reservation['number_of_guests'] > 1 ? 's' : ''; ?>
                                            </p>
                                            <p>
                                                <i class="fas fa-moon mr-2 text-indigo-600"></i>
                                                <?php echo calculateNights($reservation['check_in_date'], $reservation['check_out_date']); ?> Night<?php echo calculateNights($reservation['check_in_date'], $reservation['check_out_date']) > 1 ? 's' : ''; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-left lg:text-right">
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
                                    <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold <?php echo $badge_class; ?> mb-3">
                                        <?php echo ucfirst($reservation['status']); ?>
                                    </span>
                                    <p class="text-2xl font-bold text-gray-800 mb-2">
                                        <?php echo formatCurrency($reservation['total_price']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mb-3">
                                        Booked on <?php echo formatDate($reservation['created_at']); ?>
                                    </p>

                                    <?php if ($reservation['status'] === 'pending'): ?>
                                        <button 
                                            onclick="cancelReservation(<?php echo $reservation['id']; ?>)"
                                            class="w-full bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm transition-colors"
                                        >
                                            <i class="fas fa-times mr-1"></i> Cancel Booking
                                        </button>
                                    <?php elseif ($reservation['status'] === 'approved' && ($reservation['payment_status'] ?? 'unpaid') === 'unpaid'): ?>
                                        <div class="space-y-2">
                                            <button 
                                                onclick="returnReservation(<?php echo $reservation['id']; ?>)"
                                                class="w-full bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg text-sm transition-colors"
                                            >
                                                <i class="fas fa-door-open mr-1"></i> Check Out
                                            </button>
                                            <button 
                                                onclick="openPaymentModal(<?php echo $reservation['id']; ?>)"
                                                class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm transition-colors"
                                            >
                                                <i class="fas fa-credit-card mr-1"></i> Pay Now
                                            </button>
                                        </div>
                                    <?php elseif ($reservation['status'] === 'approved' && ($reservation['payment_status'] ?? 'unpaid') === 'paid'): ?>
                                        <div class="space-y-2">
                                            <span class="block text-xs text-green-600 font-semibold">
                                                <i class="fas fa-check-circle"></i> Paid via <?php echo ucfirst($reservation['payment_method'] ?? 'N/A'); ?>
                                            </span>
                                            <?php if (!empty($reservation['payment_reference'])): ?>
                                            <span class="block text-xs text-gray-600">
                                                <i class="fas fa-hashtag"></i> Ref: <?php echo htmlspecialchars($reservation['payment_reference']); ?>
                                            </span>
                                            <?php endif; ?>
                                            <a 
                                                href="receipt.php?id=<?php echo $reservation['id']; ?>" 
                                                target="_blank"
                                                class="w-full block bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition-colors text-center"
                                            >
                                                <i class="fas fa-receipt mr-1"></i> View Receipt
                                            </a>
                                        </div>
                                    <?php elseif ($reservation['status'] === 'completed'): ?>
                                        <a 
                                            href="receipt.php?id=<?php echo $reservation['id']; ?>" 
                                            target="_blank"
                                            class="w-full block bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition-colors text-center"
                                        >
                                            <i class="fas fa-receipt mr-1"></i> View Receipt
                                        </a>
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
            <div class="text-center py-16">
                <i class="fas fa-calendar-times text-gray-300 text-6xl mb-4"></i>
                <p class="text-white text-xl mb-2">No reservations yet</p>
                <p class="text-gray-300 mb-6">Start planning your stay with us</p>
                <a href="rooms.php" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors">
                    <i class="fas fa-bed mr-2"></i> Browse Rooms
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div id="cancelModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Cancel Reservation?</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to cancel this reservation? This action cannot be undone.</p>
            <div class="flex space-x-3">
                <button 
                    onclick="closeModal('cancelModal')"
                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 rounded-lg transition-colors"
                >
                    No, Keep It
                </button>
                <form id="cancelForm" method="POST" class="flex-1">
                    <button 
                        type="submit"
                        class="w-full bg-red-500 hover:bg-red-600 text-white py-2 rounded-lg transition-colors"
                    >
                        Yes, Cancel
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-credit-card text-green-600 mr-2"></i> Select Payment Method
            </h3>
            <form id="paymentForm" method="POST">
                <div class="space-y-3 mb-6">
                    <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-indigo-500 transition-colors">
                        <input type="radio" name="payment_method" value="gcash" class="mr-3 w-5 h-5" required onchange="toggleReferenceField()">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-800">
                                <i class="fas fa-mobile-alt text-blue-600 mr-2"></i> GCash
                            </p>
                            <p class="text-sm text-gray-500">Pay via GCash mobile app</p>
                        </div>
                    </label>
                    
                    <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-indigo-500 transition-colors">
                        <input type="radio" name="payment_method" value="paymaya" class="mr-3 w-5 h-5" required onchange="toggleReferenceField()">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-800">
                                <i class="fas fa-wallet text-green-600 mr-2"></i> PayMaya
                            </p>
                            <p class="text-sm text-gray-500">Pay via PayMaya mobile app</p>
                        </div>
                    </label>
                    
                    <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-indigo-500 transition-colors">
                        <input type="radio" name="payment_method" value="cash" class="mr-3 w-5 h-5" required onchange="toggleReferenceField()">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-800">
                                <i class="fas fa-money-bill-wave text-green-600 mr-2"></i> Cash Payment
                            </p>
                            <p class="text-sm text-gray-500">Pay at the hotel reception</p>
                        </div>
                    </label>
                </div>
                
                <div id="referenceField" class="hidden mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-hashtag mr-1"></i> Reference Number *
                    </label>
                    <input 
                        type="text" 
                        name="payment_reference" 
                        id="payment_reference"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="Enter GCash/PayMaya reference number"
                        maxlength="100"
                    >
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-info-circle"></i> Enter the transaction reference number as proof of payment
                    </p>
                </div>
                
                <div class="flex space-x-3">
                    <button 
                        type="button"
                        onclick="closeModal('paymentModal')"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 rounded-lg transition-colors"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        class="flex-1 bg-green-500 hover:bg-green-600 text-white py-2 rounded-lg transition-colors"
                    >
                        Confirm Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="feedbackModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-star text-yellow-500 mr-2"></i> How was your stay?
            </h3>
            <p class="text-gray-600 mb-4">We'd love to hear about your experience!</p>
            
            <form id="feedbackForm" method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Rating *
                    </label>
                    <div class="flex items-center space-x-2" id="ratingStars">
                        <i class="fas fa-star text-3xl text-gray-300 cursor-pointer hover:text-yellow-400 transition-colors" data-rating="1"></i>
                        <i class="fas fa-star text-3xl text-gray-300 cursor-pointer hover:text-yellow-400 transition-colors" data-rating="2"></i>
                        <i class="fas fa-star text-3xl text-gray-300 cursor-pointer hover:text-yellow-400 transition-colors" data-rating="3"></i>
                        <i class="fas fa-star text-3xl text-gray-300 cursor-pointer hover:text-yellow-400 transition-colors" data-rating="4"></i>
                        <i class="fas fa-star text-3xl text-gray-300 cursor-pointer hover:text-yellow-400 transition-colors" data-rating="5"></i>
                    </div>
                    <input type="hidden" name="rating" id="ratingValue" required>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Your Feedback (Optional)
                    </label>
                    <textarea 
                        name="feedback" 
                        rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="Tell us about your experience..."
                    ></textarea>
                </div>

                <div class="flex space-x-3">
                    <button 
                        type="button"
                        onclick="skipFeedback()"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 rounded-lg transition-colors"
                    >
                        Skip
                    </button>
                    <button 
                        type="submit"
                        class="flex-1 bg-purple-500 hover:bg-purple-600 text-white py-2 rounded-lg transition-colors"
                    >
                        <i class="fas fa-paper-plane mr-1"></i> Submit & Check Out
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentReservationId = null;
        let selectedRating = 0;

        function cancelReservation(id) {
            document.getElementById('cancelForm').action = '?cancel=' + id;
            document.getElementById('cancelModal').classList.remove('hidden');
        }

        function openPaymentModal(id) {
            document.getElementById('paymentForm').action = '?pay=' + id;
            document.getElementById('paymentModal').classList.remove('hidden');
            document.getElementById('referenceField').classList.add('hidden');
            document.getElementById('payment_reference').removeAttribute('required');
        }

        function returnReservation(id) {
            currentReservationId = id;
            document.getElementById('feedbackModal').classList.remove('hidden');
            selectedRating = 0;
            document.querySelectorAll('#ratingStars i').forEach(star => {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-gray-300');
            });
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function toggleReferenceField() {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            const referenceField = document.getElementById('referenceField');
            const referenceInput = document.getElementById('payment_reference');
            
            if (paymentMethod === 'gcash' || paymentMethod === 'paymaya') {
                referenceField.classList.remove('hidden');
                referenceInput.setAttribute('required', 'required');
            } else {
                referenceField.classList.add('hidden');
                referenceInput.removeAttribute('required');
                referenceInput.value = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('#ratingStars i');
            
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    selectedRating = parseInt(this.getAttribute('data-rating'));
                    document.getElementById('ratingValue').value = selectedRating;
                    
                    stars.forEach((s, index) => {
                        if (index < selectedRating) {
                            s.classList.remove('text-gray-300');
                            s.classList.add('text-yellow-400');
                        } else {
                            s.classList.remove('text-yellow-400');
                            s.classList.add('text-gray-300');
                        }
                    });
                });

                star.addEventListener('mouseenter', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    stars.forEach((s, index) => {
                        if (index < rating) {
                            s.classList.add('text-yellow-400');
                            s.classList.remove('text-gray-300');
                        }
                    });
                });
            });

            document.getElementById('ratingStars').addEventListener('mouseleave', function() {
                stars.forEach((s, index) => {
                    if (index < selectedRating) {
                        s.classList.add('text-yellow-400');
                        s.classList.remove('text-gray-300');
                    } else {
                        s.classList.remove('text-yellow-400');
                        s.classList.add('text-gray-300');
                    }
                });
            });

            document.getElementById('feedbackForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (selectedRating === 0) {
                    alert('Please select a rating before submitting');
                    return;
                }
                
                this.action = '?return=' + currentReservationId;
                this.submit();
            });
        });

        function skipFeedback() {

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?return=' + currentReservationId;
            
            const ratingInput = document.createElement('input');
            ratingInput.type = 'hidden';
            ratingInput.name = 'rating';
            ratingInput.value = '3'; 
            
            form.appendChild(ratingInput);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
