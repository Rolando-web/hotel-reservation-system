<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/middleware.php';

userMiddleware();

$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;

if (!$room_id) {
    setFlashMessage('error', 'Invalid room selection');
    redirect('/user/rooms.php');
}

// Get room details
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ? AND status = 'available'");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    setFlashMessage('error', 'Room not found or unavailable');
    redirect('/user/rooms.php');
}

$error = '';

// Load upcoming unavailable date ranges for this room (pending or approved)
$unavailable = [];
$uStmt = $conn->prepare("SELECT check_in_date, check_out_date FROM reservations WHERE room_id = ? AND status IN ('pending','approved') AND check_out_date >= CURDATE() ORDER BY check_in_date ASC LIMIT 10");
$uStmt->bind_param("i", $room_id);
$uStmt->execute();
$uRes = $uStmt->get_result();
while ($row = $uRes->fetch_assoc()) {
    $unavailable[] = $row;
}

// Get reviews for this room (if reviews table exists)
$reviews = [];
$avgRating = 0;
$totalReviews = 0;
$reviewsTable = $conn->query("SHOW TABLES LIKE 'reviews'");
if ($reviewsTable && $reviewsTable->num_rows > 0) {
    $rStmt = $conn->prepare("
        SELECT r.rating, r.comment, r.created_at, u.name as user_name 
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.room_id = ?
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $rStmt->bind_param("i", $room_id);
    $rStmt->execute();
    $rRes = $rStmt->get_result();
    while ($review = $rRes->fetch_assoc()) {
        $reviews[] = $review;
    }
    
    // Calculate average rating
    if (!empty($reviews)) {
        $totalRating = array_sum(array_column($reviews, 'rating'));
        $totalReviews = count($reviews);
        $avgRating = $totalRating / $totalReviews;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check_in = sanitize($_POST['check_in']);
    $check_out = sanitize($_POST['check_out']);
    $guests = (int)$_POST['guests'];
    
    // Validation
        $check_in_time = isset($_POST['check_in_time']) ? sanitize($_POST['check_in_time']) : null;

    if (empty($check_in) || empty($check_out) || $guests < 1) {
        $error = 'Please fill in all fields';
    } elseif (strtotime($check_in) < strtotime('today')) {
        $error = 'Check-in date must be today or later';
    } elseif (strtotime($check_out) <= strtotime($check_in)) {
        $error = 'Check-out date must be after check-in date';
    } elseif ($guests > $room['capacity']) {
        $error = 'Number of guests exceeds room capacity';
    } else {
        // Check if room is available for selected dates
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM reservations 
            WHERE room_id = ? 
            AND status IN ('pending', 'approved')
            AND (
                (check_in_date <= ? AND check_out_date > ?) OR
                (check_in_date < ? AND check_out_date >= ?) OR
                (check_in_date >= ? AND check_out_date <= ?)
            )
        ");
        $stmt->bind_param("issssss", $room_id, $check_in, $check_in, $check_out, $check_out, $check_in, $check_out);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
                // Find conflicting reservation to provide clearer message
                $stmt = $conn->prepare("
                    SELECT check_in_date, check_out_date FROM reservations
                    WHERE room_id = ? AND status IN ('pending','approved')
                    AND (
                        (check_in_date < ? AND check_out_date > ?) OR
                        (check_in_date < ? AND check_out_date > ?) OR
                        (check_in_date >= ? AND check_out_date <= ?)
                    )
                    ORDER BY check_in_date ASC LIMIT 1
                ");
                $stmt->bind_param("issssss", $room_id, $check_out, $check_in, $check_out, $check_in, $check_in, $check_out);
                $stmt->execute();
                $conflict = $stmt->get_result()->fetch_assoc();
            
                if ($conflict) {
                    $error = 'Room is not available for selected dates (conflicts with ' . formatDate($conflict['check_in_date']) . ' to ' . formatDate($conflict['check_out_date']) . ').';
                }
        } else {
            // Calculate total price
            $nights = calculateNights($check_in, $check_out);
            $total_price = $nights * $room['price_per_night'];
            
            // Create reservation
            $user_id = $_SESSION['user_id'];
                $stmt = $conn->prepare("
                    INSERT INTO reservations (user_id, room_id, check_in_date, check_in_time, check_out_date, number_of_guests, total_price, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->bind_param("iisssid", $user_id, $room_id, $check_in, $check_in_time, $check_out, $guests, $total_price);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Booking request submitted successfully! Awaiting admin approval.');
                redirect('/user/reservations.php');
            } else {
                $error = 'Booking failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Room - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
    <style>
        body {
            background-image: url('https://images.unsplash.com/photo-1611892440504-42a792e24d32?q=80&w=2070');
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

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="rooms.php" class="text-indigo-600 hover:text-indigo-700 font-medium">
                <i class="fas fa-arrow-left mr-1"></i> Back to Rooms
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Room Details -->
            <div>
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <?php 
                        // Handle both local paths and external URLs
                        $imageSrc = $room['image'];
                        if (!empty($imageSrc) && !filter_var($imageSrc, FILTER_VALIDATE_URL)) {
                            // Local path - ensure it starts from root
                            $imageSrc = '..' . $imageSrc;
                        }
                    ?>
                    <img 
                        src="<?php echo htmlspecialchars($imageSrc); ?>" 
                        alt="Room <?php echo htmlspecialchars($room['room_number']); ?>"
                        class="w-full h-64 object-cover"
                    >
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">Room <?php echo htmlspecialchars($room['room_number']); ?></h1>
                                <p class="text-indigo-600 font-semibold"><?php echo ucfirst($room['room_type']); ?> Room</p>
                            </div>
                            <div class="text-right">
                                <p class="text-3xl font-bold text-indigo-600"><?php echo formatCurrency($room['price_per_night']); ?></p>
                                <p class="text-sm text-gray-500">per night</p>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-4 mb-4">
                            <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($room['description']); ?></p>
                            <div class="flex items-center text-gray-700 mb-2">
                                <i class="fas fa-users text-indigo-600 mr-3"></i>
                                <span>Capacity: Up to <?php echo $room['capacity']; ?> guest<?php echo $room['capacity'] > 1 ? 's' : ''; ?></span>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-4">
                            <h3 class="font-semibold text-gray-800 mb-3">
                                <i class="fas fa-star text-yellow-500 mr-2"></i> Amenities
                            </h3>
                            <div class="grid grid-cols-2 gap-2">
                                <?php 
                                $amenities = explode(',', $room['amenities']);
                                foreach ($amenities as $amenity): 
                                ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-check text-green-500 mr-2"></i>
                                        <span><?php echo trim($amenity); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Form -->
            <div>
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Complete Your Booking</h2>

                    <?php if ($error): ?>
                        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <span><?php echo $error; ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="bookingForm">
                        <div class="space-y-5">
                            <!-- Check-in Date -->
                            <div>
                                <label for="check_in" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-calendar-check mr-1"></i> Check-in Date *
                                </label>
                                <input 
                                    type="date" 
                                    id="check_in" 
                                    name="check_in" 
                                    required
                                    min="<?php echo date('Y-m-d'); ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    value="<?php echo isset($_POST['check_in']) ? $_POST['check_in'] : ''; ?>"
                                >
                                <div class="mt-3">
                                    <label for="check_in_time" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-clock mr-1"></i> Check-in Time (optional)
                                    </label>
                                    <input 
                                        type="time" 
                                        id="check_in_time" 
                                        name="check_in_time"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        value="<?php echo isset($_POST['check_in_time']) ? $_POST['check_in_time'] : ''; ?>"
                                    >
                                    <p class="text-xs text-gray-500 mt-1">If unspecified, standard hotel check-in time applies.</p>
                                </div>
                            </div>

                            <!-- Check-out Date -->
                            <div>
                                <label for="check_out" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-calendar-times mr-1"></i> Check-out Date *
                                </label>
                                <input 
                                    type="date" 
                                    id="check_out" 
                                    name="check_out" 
                                    required
                                    min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    value="<?php echo isset($_POST['check_out']) ? $_POST['check_out'] : ''; ?>"
                                >
                            </div>

                            <!-- Unavailable Date Ranges -->
                            <?php if (!empty($unavailable)): ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <p class="text-sm font-semibold text-yellow-800 mb-2"><i class="fas fa-info-circle mr-1"></i> Unavailable date ranges for this room</p>
                                <ul class="text-sm text-yellow-800 list-disc pl-5 space-y-1">
                                    <?php foreach ($unavailable as $rng): ?>
                                        <li><?php echo formatDate($rng['check_in_date']); ?> to <?php echo formatDate($rng['check_out_date']); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>

                            <!-- Number of Guests -->
                            <div>
                                <label for="guests" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-users mr-1"></i> Number of Guests *
                                </label>
                                <select 
                                    id="guests" 
                                    name="guests" 
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                    <?php for ($i = 1; $i <= $room['capacity']; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($i == $room['capacity']) ? 'selected' : ''; ?>><?php echo $i; ?> Guest<?php echo $i > 1 ? 's' : ''; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- Booking Summary -->
                            <div id="booking-summary" class="bg-gray-50 rounded-lg p-4 hidden">
                                <h3 class="font-semibold text-gray-800 mb-3">Booking Summary</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Number of Nights:</span>
                                        <span id="nights" class="font-semibold">0</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Price per Night:</span>
                                        <span class="font-semibold"><?php echo formatCurrency($room['price_per_night']); ?></span>
                                    </div>
                                    <div class="border-t border-gray-300 pt-2 mt-2"></div>
                                    <div class="flex justify-between text-lg">
                                        <span class="text-gray-800 font-semibold">Total Price:</span>
                                        <span id="total" class="font-bold text-indigo-600">₱0.00</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button 
                                type="submit"
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-lg transition-all transform hover:scale-[1.02] active:scale-[0.98] shadow-lg hover:shadow-xl"
                            >
                                <i class="fas fa-check-circle mr-2"></i> Confirm Booking
                            </button>
                        </div>
                    </form>

                    <div class="mt-6 bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                            <div class="text-sm text-blue-800">
                                <p class="font-semibold mb-1">Important Information</p>
                                <p>Your booking request will be reviewed by our team. You will receive a confirmation once approved.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Guest Reviews Section -->
        <div class="mt-8 bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-comments text-indigo-600 mr-2"></i> Guest Reviews
                </h2>
                <?php if (!empty($reviews)): ?>
                <div class="flex items-center">
                    <div class="flex items-center bg-yellow-50 px-4 py-2 rounded-lg">
                        <i class="fas fa-star text-yellow-500 mr-2"></i>
                        <span class="text-2xl font-bold text-gray-800"><?php echo number_format($avgRating, 1); ?></span>
                        <span class="text-sm text-gray-600 ml-2">(<?php echo $totalReviews; ?> review<?php echo $totalReviews > 1 ? 's' : ''; ?>)</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($reviews)): ?>
                <div class="space-y-4">
                    <?php foreach ($reviews as $review): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-user text-indigo-600"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($review['user_name']); ?></h4>
                                        <p class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star text-sm <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php if (!empty($review['comment'])): ?>
                                <p class="text-gray-600 text-sm mt-2 ml-13"><?php echo htmlspecialchars($review['comment']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-comment-slash text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg mb-2">No reviews yet</p>
                    <p class="text-gray-400">Be the first to book and review this room!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const pricePerNight = <?php echo $room['price_per_night']; ?>;
        const checkInInput = document.getElementById('check_in');
        const checkOutInput = document.getElementById('check_out');
        const summary = document.getElementById('booking-summary');

        function calculateTotal() {
            const checkIn = new Date(checkInInput.value);
            const checkOut = new Date(checkOutInput.value);
            
            if (checkIn && checkOut && checkOut > checkIn) {
                const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
                const total = nights * pricePerNight;
                
                document.getElementById('nights').textContent = nights;
                document.getElementById('total').textContent = '₱' + total.toFixed(2);
                summary.classList.remove('hidden');
            } else {
                summary.classList.add('hidden');
            }
        }

        checkInInput.addEventListener('change', calculateTotal);
        checkOutInput.addEventListener('change', calculateTotal);
    </script>
</body>
</html>
