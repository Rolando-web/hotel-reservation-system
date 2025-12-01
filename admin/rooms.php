<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/middleware.php';

adminMiddleware();

// Handle delete
if (isset($_GET['delete']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $room_id);
    if ($stmt->execute()) {
        setFlashMessage('success', 'Room deleted successfully');
    } else {
        setFlashMessage('error', 'Cannot delete room with existing reservations');
    }
    redirect('/admin/rooms.php');
}

// Handle status toggle
if (isset($_GET['toggle']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = (int)$_GET['toggle'];
    $stmt = $conn->prepare("UPDATE rooms SET status = IF(status = 'available', 'unavailable', 'available') WHERE id = ?");
    $stmt->bind_param("i", $room_id);
    if ($stmt->execute()) {
        setFlashMessage('success', 'Room status updated');
    }
    redirect('/admin/rooms.php');
}

// Get all rooms
$rooms = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - <?php echo APP_NAME; ?></title>
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
                <h1 class="page-heading text-3xl font-bold">Manage Rooms</h1>
                <p class="text-slate-400 mt-1">Add, edit, or remove hotel rooms</p>
            </div>
            <a 
                href="room_add.php"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors shadow-lg"
            >
                <i class="fas fa-plus mr-2"></i> Add New Room
            </a>
        </div>

        <!-- Rooms Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($room = $rooms->fetch_assoc()): ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all">
                    <div class="relative h-48">
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
                            onerror="this.src='https://via.placeholder.com/400x300?text=Room+<?php echo $room['room_number']; ?>'"
                        >
                        <div class="absolute top-3 right-3">
                            <span class="bg-white px-3 py-1 rounded-full text-sm font-semibold <?php echo $room['status'] === 'available' ? 'text-green-600' : 'text-red-600'; ?> shadow-md">
                                <?php echo $room['status'] === 'available' ? 'Available' : 'Unavailable'; ?>
                            </span>
                        </div>
                    </div>

                    <div class="p-5">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Room <?php echo htmlspecialchars($room['room_number']); ?></h3>
                                <p class="text-indigo-600 font-semibold"><?php echo ucfirst($room['room_type']); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-indigo-600"><?php echo formatCurrency($room['price_per_night']); ?></p>
                                <p class="text-xs text-gray-500">per night</p>
                            </div>
                        </div>

                        <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?php echo htmlspecialchars($room['description']); ?></p>

                        <div class="flex items-center text-gray-600 text-sm mb-4">
                            <i class="fas fa-users mr-2"></i>
                            <span>Up to <?php echo $room['capacity']; ?> guest(s)</span>
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <a 
                                href="room_edit.php?id=<?php echo $room['id']; ?>"
                                class="bg-blue-500 hover:bg-blue-600 text-white text-center py-2 rounded-lg text-sm transition-colors"
                            >
                                <i class="fas fa-edit"></i>
                            </a>
                            <button 
                                onclick="toggleStatus(<?php echo $room['id']; ?>)"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 rounded-lg text-sm transition-colors"
                            >
                                <i class="fas fa-toggle-<?php echo $room['status'] === 'available' ? 'on' : 'off'; ?>"></i>
                            </button>
                            <button 
                                onclick="deleteRoom(<?php echo $room['id']; ?>)"
                                class="bg-red-500 hover:bg-red-600 text-white py-2 rounded-lg text-sm transition-colors"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Delete Room?</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to delete this room? This action cannot be undone.</p>
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

    <!-- Toggle Modal -->
    <div id="toggleModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Toggle Room Status?</h3>
            <p class="text-gray-600 mb-6">Change the availability status of this room?</p>
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

    <script>
        function deleteRoom(id) {
            document.getElementById('deleteForm').action = '?delete=' + id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function toggleStatus(id) {
            document.getElementById('toggleForm').action = '?toggle=' + id;
            document.getElementById('toggleModal').classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
    </script>
</body>
</html>
