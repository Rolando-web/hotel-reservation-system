<nav class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="<?php echo APP_URL; ?>/index.php" class="flex items-center space-x-2">
                    <i class="fas fa-hotel text-indigo-600 text-2xl"></i>
                    <span class="text-xl font-bold text-gray-800"><?php echo APP_NAME; ?></span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-6">
                <a href="<?php echo APP_URL; ?>/user/dashboard.php" class="text-gray-700 hover:text-indigo-600 font-medium transition-colors">
                    <i class="fas fa-home mr-1"></i> Dashboard
                </a>
                <a href="<?php echo APP_URL; ?>/user/rooms.php" class="text-gray-700 hover:text-indigo-600 font-medium transition-colors">
                    <i class="fas fa-bed mr-1"></i> Rooms
                </a>
                <a href="<?php echo APP_URL; ?>/user/reservations.php" class="text-gray-700 hover:text-indigo-600 font-medium transition-colors">
                    <i class="fas fa-calendar-check mr-1"></i> My Bookings
                </a>
                <?php 
                    // Notifications count (safe if table doesn't exist)
                    $unread_count = 0; $latest_notes = [];
                    if (isset($_SESSION['user_id'])) {
                        $uid = (int)$_SESSION['user_id'];
                        $hasNotifications = false;
                        try {
                            $chk = $conn->query("SHOW TABLES LIKE 'notifications'");
                            $hasNotifications = $chk && $chk->num_rows > 0;
                        } catch (Exception $e) {
                            $hasNotifications = false;
                        }
                        if ($hasNotifications) {
                            $ns = $conn->prepare("SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND read_at IS NULL");
                            $ns->bind_param("i", $uid);
                            $ns->execute();
                            $cRes = $ns->get_result()->fetch_assoc();
                            $unread_count = (int)$cRes['c'];
                            $nl = $conn->prepare("SELECT id, message, link, created_at, read_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
                            $nl->bind_param("i", $uid);
                            $nl->execute();
                            $latest_notes = $nl->get_result()->fetch_all(MYSQLI_ASSOC);
                        }
                    }
                ?>
                <div class="relative">
                    <button id="notif-btn" class="relative text-gray-700 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-bell text-xl"></i>
                        <?php if ($unread_count > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5">
                            <?php echo $unread_count; ?>
                        </span>
                        <?php endif; ?>
                    </button>
                    <div id="notif-menu" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                        <div class="p-3 border-b"><span class="font-semibold text-gray-800">Notifications</span></div>
                        <div class="max-h-72 overflow-auto">
                            <?php if (empty($latest_notes)): ?>
                                <div class="p-4 text-sm text-gray-500">No notifications</div>
                            <?php else: foreach ($latest_notes as $n): ?>
                                <a href="<?php echo htmlspecialchars($n['link'] ?: APP_URL.'/user/reservations.php'); ?>" class="block px-4 py-3 hover:bg-gray-50">
                                    <p class="text-sm text-gray-800 <?php echo $n['read_at'] ? '' : 'font-semibold'; ?>"><?php echo htmlspecialchars($n['message']); ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo date('M d, Y h:i A', strtotime($n['created_at'])); ?></p>
                                </a>
                            <?php endforeach; endif; ?>
                        </div>
                        <div class="p-2 border-t text-right">
                            <a href="<?php echo APP_URL; ?>/user/notifications.php" class="text-xs text-indigo-600 hover:text-indigo-700">View all</a>
                        </div>
                    </div>
                </div>
                <a href="<?php echo APP_URL; ?>/user/profile.php" class="text-gray-700 hover:text-indigo-600 font-medium transition-colors">
                    <i class="fas fa-user mr-1"></i> Profile
                </a>
                <a href="<?php echo APP_URL; ?>/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt mr-1"></i> Logout
                </a>
            </div>

            <!-- Mobile Menu Button -->
            <div class="md:hidden">
                <button id="mobile-menu-btn" class="text-gray-700 hover:text-indigo-600 focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-200">
        <div class="px-4 py-3 space-y-3">
            <a href="<?php echo APP_URL; ?>/user/dashboard.php" class="block text-gray-700 hover:text-indigo-600 font-medium">
                <i class="fas fa-home mr-2"></i> Dashboard
            </a>
            <a href="<?php echo APP_URL; ?>/user/rooms.php" class="block text-gray-700 hover:text-indigo-600 font-medium">
                <i class="fas fa-bed mr-2"></i> Rooms
            </a>
            <a href="<?php echo APP_URL; ?>/user/reservations.php" class="block text-gray-700 hover:text-indigo-600 font-medium">
                <i class="fas fa-calendar-check mr-2"></i> My Bookings
            </a>
            <a href="<?php echo APP_URL; ?>/user/profile.php" class="block text-gray-700 hover:text-indigo-600 font-medium">
                <i class="fas fa-user mr-2"></i> Profile
            </a>
            <a href="<?php echo APP_URL; ?>/logout.php" class="block text-red-600 hover:text-red-700 font-medium">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
        </div>
    </div>
</nav>

<script>
    document.getElementById('mobile-menu-btn').addEventListener('click', function() {
        const menu = document.getElementById('mobile-menu');
        menu.classList.toggle('hidden');
    });
    const nb = document.getElementById('notif-btn');
    const nm = document.getElementById('notif-menu');
    if (nb) {
        nb.addEventListener('click', () => nm.classList.toggle('hidden'));
        document.addEventListener('click', (e) => {
            if (!nb.contains(e.target) && !nm.contains(e.target)) { nm.classList.add('hidden'); }
        });
    }
</script>
