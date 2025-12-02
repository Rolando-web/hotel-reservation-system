<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/middleware.php';

userMiddleware();

// Get filter parameters
$room_type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$query = "SELECT * FROM rooms WHERE status = 'available'";
$params = [];
$types = '';

if ($room_type) {
    $query .= " AND room_type = ?";
    $params[] = $room_type;
    $types .= 's';
}

if ($max_price > 0) {
    $query .= " AND price_per_night <= ?";
    $params[] = $max_price;
    $types .= 'i';
}

if ($search) {
    $query .= " AND (room_number LIKE ? OR description LIKE ? OR amenities LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$query .= " ORDER BY price_per_night ASC";

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rooms = $stmt->get_result();

// Calculate currently occupied rooms: today is between check-in and check-out (exclusive)
$occupiedRooms = [];
$occ = $conn->query("SELECT DISTINCT room_id FROM reservations WHERE status IN ('approved') AND CURDATE() >= check_in_date AND CURDATE() < check_out_date");
if ($occ) {
    while ($r = $occ->fetch_assoc()) { $occupiedRooms[] = (int)$r['room_id']; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Rooms - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
    <style>
        body {
            background-image: url('https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=2070');
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
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white">Browse Available Rooms</h1>
            <p class="text-gray-300 mt-1">Find your perfect accommodation</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search rooms..."
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                </div>

                <!-- Room Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Room Type</label>
                    <select 
                        name="type"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">All Types</option>
                        <option value="single" <?php echo $room_type === 'single' ? 'selected' : ''; ?>>Single</option>
                        <option value="double" <?php echo $room_type === 'double' ? 'selected' : ''; ?>>Double</option>
                        <option value="suite" <?php echo $room_type === 'suite' ? 'selected' : ''; ?>>Suite</option>
                        <option value="deluxe" <?php echo $room_type === 'deluxe' ? 'selected' : ''; ?>>Deluxe</option>
                        <option value="family" <?php echo $room_type === 'family' ? 'selected' : ''; ?>>Family</option>
                    </select>
                </div>

                <!-- Max Price -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Max Price/Night</label>
                    <input 
                        type="number" 
                        name="max_price" 
                        placeholder="Any price"
                        value="<?php echo $max_price > 0 ? $max_price : ''; ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                </div>

                <!-- Buttons -->
                <div class="flex items-end space-x-2">
                    <button 
                        type="submit"
                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg transition-colors"
                    >
                        <i class="fas fa-search mr-1"></i> Search
                    </button>
                    <a 
                        href="rooms.php"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Rooms Grid -->
        <?php if ($rooms->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($room = $rooms->fetch_assoc()): ?>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden transform hover:scale-105 transition-all hover:shadow-xl">
                        <!-- Room Image -->
                        <div class="relative h-48 overflow-hidden">
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
                                class="w-full h-full object-cover"
                                loading="lazy"
                                onerror="this.src='https://via.placeholder.com/400x300?text=Room+<?php echo $room['room_number']; ?>'"
                            >
                            <div class="absolute top-3 right-3">
                                <span class="bg-white px-3 py-1 rounded-full text-sm font-semibold text-indigo-600 shadow-md">
                                    <?php echo ucfirst($room['room_type']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Room Details -->
                        <div class="p-5">
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="text-xl font-bold text-gray-800">Room <?php echo htmlspecialchars($room['room_number']); ?></h3>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-indigo-600"><?php echo formatCurrency($room['price_per_night']); ?></p>
                                    <p class="text-sm text-gray-500">per night</p>
                                </div>
                            </div>

                            <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?php echo htmlspecialchars($room['description']); ?></p>

                            <div class="flex items-center text-gray-600 text-sm mb-4">
                                <i class="fas fa-users mr-2"></i>
                                <span>Up to <?php echo $room['capacity']; ?> guest<?php echo $room['capacity'] > 1 ? 's' : ''; ?></span>
                            </div>

                            <!-- Amenities -->
                            <div class="mb-4">
                                <?php 
                                $amenities = explode(',', $room['amenities']);
                                $display_amenities = array_slice($amenities, 0, 3);
                                ?>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($display_amenities as $amenity): ?>
                                        <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">
                                            <?php echo trim($amenity); ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if (count($amenities) > 3): ?>
                                        <span class="text-xs text-indigo-600 px-2 py-1">
                                            +<?php echo count($amenities) - 3; ?> more
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Book Button -->
                            <?php $isOccupied = in_array((int)$room['id'], $occupiedRooms, true); ?>
                            <?php if ($isOccupied): ?>
                                <button 
                                    class="block w-full bg-gray-400 cursor-not-allowed text-white text-center py-3 rounded-lg font-semibold"
                                    disabled
                                    title="This room is currently occupied"
                                >
                                    <i class="fas fa-bed mr-2"></i> Currently Occupied
                                </button>
                            <?php else: ?>
                                <a 
                                    href="book.php?room_id=<?php echo $room['id']; ?>"
                                    class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white text-center py-3 rounded-lg font-semibold transition-colors"
                                >
                                    <i class="fas fa-calendar-plus mr-2"></i> Book Now
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-16">
                <i class="fas fa-bed text-gray-300 text-6xl mb-4"></i>
                <p class="text-gray-500 text-xl mb-2">No rooms found</p>
                <p class="text-gray-400 mb-6">Try adjusting your filters</p>
                <a href="rooms.php" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors">
                    <i class="fas fa-redo mr-2"></i> Clear Filters
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
