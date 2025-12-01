<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/middleware.php';

userMiddleware();

$reservation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// Get reservation details with user and room info
$stmt = $conn->prepare("
    SELECT r.*, 
           u.name as guest_name, u.email as guest_email, u.phone as guest_phone, u.address as guest_address,
           rm.room_number, rm.room_type, rm.price_per_night, rm.amenities
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN rooms rm ON r.room_id = rm.id
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->bind_param("ii", $reservation_id, $user_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();

if (!$reservation) {
    setFlashMessage('error', 'Receipt not found');
    redirect('/user/reservations.php');
}

$nights = calculateNights($reservation['check_in_date'], $reservation['check_out_date']);
$subtotal = $reservation['total_price'];
$tax = $subtotal * 0.10; // 10% tax
$total = $subtotal + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo str_pad($reservation['id'], 6, '0', STR_PAD_LEFT); ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Action Buttons -->
    <div class="no-print fixed top-4 right-4 flex space-x-2 z-50">
        <button 
            onclick="window.print()"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-lg transition-colors"
        >
            <i class="fas fa-print mr-2"></i> Print
        </button>
        <button 
            onclick="downloadPDF()"
            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow-lg transition-colors"
        >
            <i class="fas fa-download mr-2"></i> Download PDF
        </button>
        <a 
            href="reservations.php"
            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg shadow-lg transition-colors"
        >
            <i class="fas fa-times mr-2"></i> Close
        </a>
    </div>

    <!-- Receipt Content -->
    <div class="min-h-screen py-8 px-4">
        <div id="receipt" class="max-w-4xl mx-auto bg-white shadow-2xl rounded-xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-8 py-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">
                            <i class="fas fa-hotel mr-2"></i> <?php echo APP_NAME; ?>
                        </h1>
                        <p class="text-indigo-100">Your Comfort is Our Priority</p>
                        <div class="mt-4 text-sm text-indigo-100">
                            <p><i class="fas fa-map-marker-alt mr-2"></i> 123 Hotel Street, City, State 12345</p>
                            <p><i class="fas fa-phone mr-2"></i> +1 (555) 123-4567</p>
                            <p><i class="fas fa-envelope mr-2"></i> info@hotelparadise.com</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="bg-white text-indigo-600 px-4 py-2 rounded-lg inline-block">
                            <p class="text-xs font-semibold">RECEIPT</p>
                            <p class="text-2xl font-bold">#<?php echo str_pad($reservation['id'], 6, '0', STR_PAD_LEFT); ?></p>
                        </div>
                        <div class="mt-4 text-sm">
                            <p>Date: <?php echo date('M d, Y'); ?></p>
                            <?php if ($reservation['payment_date']): ?>
                                <p>Paid: <?php echo date('M d, Y', strtotime($reservation['payment_date'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guest & Reservation Info -->
            <div class="px-8 py-6 border-b border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">Guest Information</h3>
                        <div class="text-gray-800">
                            <p class="font-bold text-lg"><?php echo htmlspecialchars($reservation['guest_name']); ?></p>
                            <p class="text-sm mt-1"><?php echo htmlspecialchars($reservation['guest_email']); ?></p>
                            <?php if ($reservation['guest_phone']): ?>
                                <p class="text-sm"><?php echo htmlspecialchars($reservation['guest_phone']); ?></p>
                            <?php endif; ?>
                            <?php if ($reservation['guest_address']): ?>
                                <p class="text-sm mt-1"><?php echo htmlspecialchars($reservation['guest_address']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">Reservation Details</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Booking ID:</span>
                                <span class="font-semibold">RES-<?php echo str_pad($reservation['id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Booked On:</span>
                                <span class="font-semibold"><?php echo formatDate($reservation['created_at']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span class="font-semibold text-green-600"><?php echo ucfirst($reservation['status']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Payment:</span>
                                <span class="font-semibold text-green-600">
                                    <?php echo ucfirst($reservation['payment_status']); ?>
                                    <?php if ($reservation['payment_method']): ?>
                                        (<?php echo ucfirst($reservation['payment_method']); ?>)
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if (isset($reservation['payment_reference']) && !empty($reservation['payment_reference'])): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Reference #:</span>
                                <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($reservation['payment_reference']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Room Details -->
            <div class="px-8 py-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Room Details</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h4 class="text-xl font-bold text-gray-800">Room <?php echo htmlspecialchars($reservation['room_number']); ?></h4>
                            <p class="text-indigo-600 font-semibold"><?php echo ucfirst($reservation['room_type']); ?> Room</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-gray-800"><?php echo formatCurrency($reservation['price_per_night']); ?></p>
                            <p class="text-sm text-gray-500">per night</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600 mb-1"><i class="fas fa-calendar-check text-green-600 mr-2"></i> Check-in</p>
                            <p class="font-semibold"><?php echo formatDate($reservation['check_in_date']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600 mb-1"><i class="fas fa-calendar-times text-red-600 mr-2"></i> Check-out</p>
                            <p class="font-semibold"><?php echo formatDate($reservation['check_out_date']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600 mb-1"><i class="fas fa-users text-indigo-600 mr-2"></i> Guests</p>
                            <p class="font-semibold"><?php echo $reservation['number_of_guests']; ?> Guest<?php echo $reservation['number_of_guests'] > 1 ? 's' : ''; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Price Breakdown -->
            <div class="px-8 py-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Payment Summary</h3>
                <div class="space-y-3">
                    <div class="flex justify-between text-gray-700">
                        <span>Room Rate (<?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?>)</span>
                        <span><?php echo formatCurrency($reservation['price_per_night']); ?> × <?php echo $nights; ?></span>
                    </div>
                    <div class="flex justify-between text-gray-700">
                        <span>Subtotal</span>
                        <span class="font-semibold"><?php echo formatCurrency($subtotal); ?></span>
                    </div>
                    <div class="flex justify-between text-gray-700">
                        <span>Tax (10%)</span>
                        <span class="font-semibold"><?php echo formatCurrency($tax); ?></span>
                    </div>
                    <div class="border-t-2 border-gray-300 pt-3 mt-3"></div>
                    <div class="flex justify-between text-xl font-bold text-gray-900">
                        <span>Total Amount</span>
                        <span class="text-indigo-600"><?php echo formatCurrency($total); ?></span>
                    </div>
                    
                    <?php if ($reservation['payment_status'] === 'paid'): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mt-4">
                            <div class="flex items-center text-green-800">
                                <i class="fas fa-check-circle text-2xl mr-3"></i>
                                <div>
                                    <p class="font-bold">Payment Received</p>
                                    <p class="text-sm">Thank you for your payment via <?php echo ucfirst($reservation['payment_method']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4">
                            <div class="flex items-center text-yellow-800">
                                <i class="fas fa-exclamation-triangle text-2xl mr-3"></i>
                                <div>
                                    <p class="font-bold">Payment Pending</p>
                                    <p class="text-sm">Please complete payment to confirm your reservation</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Admin Notes -->
            <?php if ($reservation['admin_notes']): ?>
                <div class="px-8 py-6 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Special Notes</h3>
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                        <p class="text-gray-700"><?php echo htmlspecialchars($reservation['admin_notes']); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="px-8 py-6 bg-gray-50">
                <div class="text-center text-sm text-gray-600">
                    <p class="mb-2">Thank you for choosing <?php echo APP_NAME; ?>!</p>
                    <p class="text-xs">This is a computer-generated receipt and does not require a signature.</p>
                    <p class="text-xs mt-2">For inquiries, please contact us at info@hotelparadise.com or call +1 (555) 123-4567</p>
                    
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-xs text-gray-500">
                            Receipt generated on <?php echo date('F d, Y \a\t h:i A'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function downloadPDF() {
            const element = document.getElementById('receipt');
            const opt = {
                margin: 0.5,
                filename: 'Receipt-<?php echo str_pad($reservation['id'], 6, '0', STR_PAD_LEFT); ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
