<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/middleware.php';

adminMiddleware();

$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $room_id > 0;

$room = null;
if ($is_edit) {
    $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    
    if (!$room) {
        setFlashMessage('error', 'Room not found');
        redirect('/admin/rooms.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_number = sanitize($_POST['room_number']);
    $room_type = sanitize($_POST['room_type']);
    $capacity = (int)$_POST['capacity'];
    $price = (float)$_POST['price_per_night'];
    $description = sanitize($_POST['description']);
    $amenities = sanitize($_POST['amenities']);
    $status = sanitize($_POST['status']);
    $imagePath = $is_edit && $room ? $room['image'] : null;

    // If a hidden current_image is sent, use it as fallback when no upload
    if (isset($_POST['current_image']) && $_POST['current_image'] !== '') {
        $imagePath = sanitize($_POST['current_image']);
    }

    // Handle optional image upload
    if (isset($_FILES['image']) && is_array($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/webp' => '.webp', 'image/gif' => '.gif'];
            $mime = mime_content_type($_FILES['image']['tmp_name']);
            if (!isset($allowed[$mime])) {
                $error = 'Invalid image type. Allowed: JPG, PNG, WEBP, GIF';
            } else {
                $ext = $allowed[$mime];
                $uploadsDir = dirname(__DIR__) . '/uploads/rooms';
                if (!is_dir($uploadsDir)) {
                    @mkdir($uploadsDir, 0777, true);
                }
                $base = 'room_' . time() . '_' . bin2hex(random_bytes(4)) . $ext;
                $targetFs = $uploadsDir . '/' . $base;
                $webRel = '/uploads/rooms/' . $base;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFs)) {
                    $imagePath = $webRel;
                } else {
                    $error = 'Failed to upload image. Please try again.';
                }
            }
        } else {
            $error = 'Image upload error. Please choose a valid file.';
        }
    }
    
    if (empty($room_number) || empty($room_type) || $capacity < 1 || $price <= 0) {
        $error = 'Please fill in all required fields';
    } elseif (!$error) {
        if ($imagePath === null) { $imagePath = ''; }
        
        // Check for duplicate room number
        if ($is_edit) {
            $checkStmt = $conn->prepare("SELECT id FROM rooms WHERE room_number = ? AND id != ?");
            $checkStmt->bind_param("si", $room_number, $room_id);
        } else {
            $checkStmt = $conn->prepare("SELECT id FROM rooms WHERE room_number = ?");
            $checkStmt->bind_param("s", $room_number);
        }
        $checkStmt->execute();
        $existingRoom = $checkStmt->get_result()->fetch_assoc();
        if ($existingRoom) {
            $error = '⚠️ Room number "' . htmlspecialchars($room_number) . '" is already taken. Please choose a unique room number to avoid conflicts.';
        } else {
            if ($is_edit) {
                $stmt = $conn->prepare("UPDATE rooms SET room_number = ?, room_type = ?, capacity = ?, price_per_night = ?, description = ?, amenities = ?, image = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssidssssi", $room_number, $room_type, $capacity, $price, $description, $amenities, $imagePath, $status, $room_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type, capacity, price_per_night, description, amenities, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiissss", $room_number, $room_type, $capacity, $price, $description, $amenities, $imagePath, $status);
            }
            
            if ($stmt->execute()) {
                setFlashMessage('success', $is_edit ? 'Room updated successfully' : 'Room added successfully');
                redirect('/admin/rooms.php');
            } else {
                $error = 'Failed to save room. Please try again.';
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
    <title><?php echo $is_edit ? 'Edit' : 'Add'; ?> Room - <?php echo APP_NAME; ?></title>
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

    <div class="content-wrap max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-slate-100">
        <div class="mb-6">
            <a href="rooms.php" class="text-indigo-600 hover:text-indigo-700 font-medium">
                <i class="fas fa-arrow-left mr-1"></i> Back to Rooms
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-md p-8">
            <h1 class="page-heading text-2xl text-black font-bold mb-6"><?php echo $is_edit ? 'Edit' : 'Add New'; ?> Room</h1>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="room_number" class="block text-sm font-medium text-black mb-2">Room Number *</label>
                        <input 
                            type="text" 
                            id="room_number" 
                            name="room_number" 
                            required
                            value="<?php echo $is_edit ? htmlspecialchars($room['room_number']) : ''; ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg text-black focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                    </div>

                    <div>
                        <label for="room_type" class="block text-sm font-medium text-black mb-2">Room Type *</label>
                        <select 
                            id="room_type" 
                            name="room_type" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 text-black focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="single" <?php echo ($is_edit && $room['room_type'] === 'single') ? 'selected' : ''; ?>>Single</option>
                            <option value="double" <?php echo ($is_edit && $room['room_type'] === 'double') ? 'selected' : ''; ?>>Double</option>
                            <option value="suite" <?php echo ($is_edit && $room['room_type'] === 'suite') ? 'selected' : ''; ?>>Suite</option>
                            <option value="deluxe" <?php echo ($is_edit && $room['room_type'] === 'deluxe') ? 'selected' : ''; ?>>Deluxe</option>
                            <option value="family" <?php echo ($is_edit && $room['room_type'] === 'family') ? 'selected' : ''; ?>>Family</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="capacity" class="block text-sm font-medium text-black mb-2">Capacity (Guests) *</label>
                        <input 
                            type="number" 
                            id="capacity" 
                            name="capacity" 
                            required
                            min="1"
                            value="<?php echo $is_edit ? $room['capacity'] : '1'; ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg  text-black focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                    </div>

                    <div>
                        <label for="price_per_night" class="block text-sm font-medium text-black mb-2">Price per Night (₱) *</label>
                        <input 
                            type="number" 
                            id="price_per_night" 
                            name="price_per_night" 
                            required
                            min="0"
                            step="0.01"
                            value="<?php echo $is_edit ? $room['price_per_night'] : ''; ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg  text-black focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-black mb-2">Description</label>
                    <textarea 
                        id="description" 
                        name="description"
                        rows="3"
                        class="w-full px-4 py-3 border border-gray-300 text-black rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    ><?php echo $is_edit ? htmlspecialchars($room['description']) : ''; ?></textarea>
                </div>

                <div>
                    <label for="amenities" class="block text-sm font-medium text-black mb-2">Amenities (comma-separated)</label>
                    <textarea 
                        id="amenities" 
                        name="amenities"
                        rows="2"
                        placeholder="Free WiFi, Air Conditioning, TV"
                        class="w-full px-4 py-3 border border-gray-300 text-black rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    ><?php echo $is_edit ? htmlspecialchars($room['amenities']) : ''; ?></textarea>
                </div>

                <div>
                    <label for="image" class="block text-sm font-medium text-black mb-2">Room Image</label>
                    <?php if ($is_edit && !empty($room['image'])): ?>
                        <div class="mb-3 flex items-center space-x-3">
                            <img src="<?php echo htmlspecialchars($room['image']); ?>" alt="Current image" class="w-24 h-24 object-cover rounded">
                            <span class="text-xs text-black">Current image</span>
                        </div>
                        <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($room['image']); ?>">
                    <?php endif; ?>
                    <input 
                        type="file" 
                        id="image" 
                        name="image"
                        accept="image/*"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg text-black focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                    <p class="text-xs text-gray-500 mt-1">Upload JPG, PNG, WEBP, or GIF. Max ~5MB.</p>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-black mb-2">Status *</label>
                    <select 
                        id="status" 
                        name="status" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg text-black focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="available" <?php echo ($is_edit && $room['status'] === 'available') ? 'selected' : ''; ?>>Available</option>
                        <option value="unavailable" <?php echo ($is_edit && $room['status'] === 'unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-3 pt-6">
                    <a 
                        href="rooms.php"
                        class="px-6 py-3 border border-gray-300 rounded-lg text-black hover:bg-gray-50 transition-colors"
                    >
                        Cancel
                    </a>
                    <button 
                        type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-lg font-semibold transition-all shadow-lg"
                    >
                        <i class="fas fa-save mr-2"></i> <?php echo $is_edit ? 'Update' : 'Add'; ?> Room
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
