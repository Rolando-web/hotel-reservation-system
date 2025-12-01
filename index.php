<?php
require_once __DIR__ . '/config/config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/user/dashboard.php');
    }
}

// Get featured rooms
$featured_rooms = $conn->query("SELECT * FROM rooms WHERE status = 'available' ORDER BY RAND() LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Your Comfort is Our Priority</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .hero-gradient {
            background-image: url('https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?q=80&w=2070');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
        }
        .hero-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(102, 126, 234, 0.7);
            z-index: 0;
        }
        .hero-gradient > * {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body class="bg-white">
    <!-- Navigation -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center space-x-2">
                        <i class="fas fa-hotel text-indigo-600 text-2xl"></i>
                        <span class="text-xl font-bold text-gray-800"><?php echo APP_NAME; ?></span>
                    </a>
                </div>

                <div class="hidden md:flex items-center space-x-6">
                    <a href="#features" class="text-gray-700 hover:text-indigo-600 font-medium transition-colors">Features</a>
                    <a href="#rooms" class="text-gray-700 hover:text-indigo-600 font-medium transition-colors">Rooms</a>
                    <a href="#about" class="text-gray-700 hover:text-indigo-600 font-medium transition-colors">About</a>
                    <a href="login.php" class="text-gray-700 hover:text-indigo-600 font-medium transition-colors">
                        <i class="fas fa-sign-in-alt mr-1"></i> Login
                    </a>
                    <a href="register.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        Sign Up
                    </a>
                </div>

                <div class="md:hidden">
                    <button id="mobile-menu-btn" class="text-gray-700 hover:text-indigo-600">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-200">
            <div class="px-4 py-3 space-y-3">
                <a href="#features" class="block text-gray-700 hover:text-indigo-600 font-medium">Features</a>
                <a href="#rooms" class="block text-gray-700 hover:text-indigo-600 font-medium">Rooms</a>
                <a href="#about" class="block text-gray-700 hover:text-indigo-600 font-medium">About</a>
                <a href="login.php" class="block text-gray-700 hover:text-indigo-600 font-medium">Login</a>
                <a href="register.php" class="block bg-indigo-600 text-white text-center py-2 rounded-lg font-semibold">Sign Up</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-bold mb-6 animate-fade-in">
                    Welcome to <?php echo APP_NAME; ?>
                </h1>
                <p class="text-xl md:text-2xl text-indigo-100 mb-8 max-w-3xl mx-auto">
                    Experience luxury and comfort in our carefully curated rooms. Your perfect stay awaits.
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="register.php" class="bg-white text-indigo-600 hover:bg-indigo-50 px-8 py-4 rounded-lg font-bold text-lg transition-all transform hover:scale-105 shadow-lg">
                        <i class="fas fa-user-plus mr-2"></i> Get Started
                    </a>
                    <a href="#rooms" class="bg-indigo-700 hover:bg-indigo-800 text-white px-8 py-4 rounded-lg font-bold text-lg transition-all transform hover:scale-105 shadow-lg">
                        <i class="fas fa-bed mr-2"></i> Browse Rooms
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gradient-to-br from-purple-50 to-indigo-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Why Choose Us?</h2>
                <p class="text-xl text-gray-600">Experience the best hospitality with our premium services</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-xl shadow-lg p-8 transform hover:scale-105 transition-all text-center border-t-4 border-indigo-600">
                    <div class="bg-indigo-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-clock text-indigo-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">24/7 Support</h3>
                    <p class="text-gray-600">Round-the-clock customer service to ensure your comfort and satisfaction</p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-8 transform hover:scale-105 transition-all text-center border-t-4 border-green-600">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Easy Booking</h3>
                    <p class="text-gray-600">Simple and secure online reservation system for hassle-free bookings</p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-8 transform hover:scale-105 transition-all text-center border-t-4 border-yellow-500">
                    <div class="bg-yellow-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-star text-yellow-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Premium Quality</h3>
                    <p class="text-gray-600">Luxury rooms with top-notch amenities for an unforgettable experience</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Rooms Section -->
    <section id="rooms" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Featured Rooms</h2>
                <p class="text-xl text-gray-600">Discover our most popular accommodations</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php while ($room = $featured_rooms->fetch_assoc()): ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden transform hover:scale-105 transition-all">
                        <div class="relative h-48">
                            <img 
                                src="<?php echo htmlspecialchars($room['image']); ?>" 
                                alt="Room <?php echo htmlspecialchars($room['room_number']); ?>"
                                class="w-full h-full object-cover"
                                onerror="this.src='https://via.placeholder.com/400x300?text=Room+<?php echo $room['room_number']; ?>'"
                            >
                            <div class="absolute top-3 right-3">
                                <span class="bg-white px-3 py-1 rounded-full text-sm font-semibold text-indigo-600 shadow-md">
                                    <?php echo ucfirst($room['room_type']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
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
                                <span>Up to <?php echo $room['capacity']; ?> guest(s)</span>
                            </div>
                            <a href="register.php" class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white text-center py-3 rounded-lg font-semibold transition-colors">
                                Book Now
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="text-center mt-12">
                <a href="register.php" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors">
                    View All Rooms <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-4xl font-bold text-gray-800 mb-6">About <?php echo APP_NAME; ?></h2>
                    <p class="text-lg text-gray-600 mb-6">
                        Welcome to <?php echo APP_NAME; ?>, where luxury meets comfort. We pride ourselves on providing exceptional hospitality and unforgettable experiences for all our guests.
                    </p>
                    <p class="text-lg text-gray-600 mb-6">
                        Our modern rooms are equipped with premium amenities, ensuring your stay is comfortable, convenient, and memorable. Whether you're traveling for business or leisure, we have the perfect accommodation for you.
                    </p>
                    <div class="grid grid-cols-2 gap-6 mt-8">
                        <div>
                            <p class="text-4xl font-bold text-indigo-600 mb-2">500+</p>
                            <p class="text-gray-600">Happy Guests</p>
                        </div>
                        <div>
                            <p class="text-4xl font-bold text-indigo-600 mb-2">50+</p>
                            <p class="text-gray-600">Premium Rooms</p>
                        </div>
                        <div>
                            <p class="text-4xl font-bold text-indigo-600 mb-2">24/7</p>
                            <p class="text-gray-600">Support</p>
                        </div>
                        <div>
                            <p class="text-4xl font-bold text-indigo-600 mb-2">5★</p>
                            <p class="text-gray-600">Rated Service</p>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <img src="https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=400" alt="Hotel" class="rounded-lg shadow-lg">
                    <img src="https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=400" alt="Room" class="rounded-lg shadow-lg mt-8">
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="hero-gradient text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold mb-6">Ready to Book Your Stay?</h2>
            <p class="text-xl text-indigo-100 mb-8 max-w-2xl mx-auto">
                Join thousands of satisfied guests who have experienced the comfort and luxury of <?php echo APP_NAME; ?>
            </p>
            <a href="register.php" class="inline-block bg-white text-indigo-600 hover:bg-indigo-50 px-8 py-4 rounded-lg font-bold text-lg transition-all transform hover:scale-105 shadow-lg">
                <i class="fas fa-calendar-check mr-2"></i> Book Now
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-2xl font-bold mb-4">
                        <i class="fas fa-hotel mr-2"></i> <?php echo APP_NAME; ?>
                    </h3>
                    <p class="text-gray-400">Your comfort is our priority. Experience luxury and hospitality like never before.</p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-gray-400 hover:text-white transition-colors">Features</a></li>
                        <li><a href="#rooms" class="text-gray-400 hover:text-white transition-colors">Rooms</a></li>
                        <li><a href="#about" class="text-gray-400 hover:text-white transition-colors">About</a></li>
                        <li><a href="login.php" class="text-gray-400 hover:text-white transition-colors">Login</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contact</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><i class="fas fa-envelope mr-2"></i> info@hotelparadise.com</li>
                        <li><i class="fas fa-phone mr-2"></i> +1 (555) 123-4567</li>
                        <li><i class="fas fa-map-marker-alt mr-2"></i> 123 Hotel St, City</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 <?php echo APP_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
