# 🏨 Hotel Room Reservation System

A full-stack hotel room reservation system with role-based access control, featuring a minimal yet visually appealing design built with PHP, MySQL, and Tailwind CSS.

![Hotel Paradise](https://img.shields.io/badge/Status-Complete-success)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)

## ✨ Features

### 👥 User Features
- **User Registration & Login** - Secure authentication with password hashing
- **Browse Available Rooms** - Filter by type, price, and search functionality
- **Make Reservations** - Easy booking with date selection and guest count
- **Reservation History** - View all past and current bookings
- **Payment Options** - Pay via Online or Cash payment methods
- **Cancel Reservations** - Cancel pending bookings
- **Check Out** - Return room after stay completion
- **Downloadable Receipt** - Generate PDF receipts for paid reservations
- **Profile Management** - Update personal information and change password

### 👨‍💼 Admin Features
- **Admin Dashboard** - Overview with statistics and charts
- **Reservation Management** - Approve/reject booking requests with notes
- **Room Management (CRUD)**
  - Add new rooms
  - View all rooms
  - Edit room details
  - Delete rooms
  - Toggle room availability
- **User Management (CRUD)**
  - View all users
  - Toggle user status (active/inactive)
  - Delete users
  - View user statistics

### 🎨 Design Features
- **Clean Modern Interface** - Minimal design with Tailwind CSS
- **Responsive Layout** - Mobile-first design works on all devices
- **Smooth Animations** - Hover effects and transitions throughout
- **Interactive Elements** - Modals, toasts, and status badges
- **Visual Charts** - Dashboard analytics with Chart.js
- **High-Quality Images** - Beautiful room imagery from Unsplash

## 📋 Prerequisites

- **XAMPP** (or any PHP development environment)
  - PHP 7.4 or higher
  - MySQL 5.7 or higher
  - Apache Web Server
- Modern web browser (Chrome, Firefox, Safari, Edge)

## 🚀 Installation Guide

### Step 1: Download and Extract

1. Extract the project files to your XAMPP htdocs directory:
   ```
   d:\Xamp\htdocs\hotel-room-reservation\
   ```

### Step 2: Database Setup

1. Open **phpMyAdmin** in your browser:
   ```
   http://localhost/phpmyadmin
   ```

2. **IMPORTANT:** If a database named `hotel_reservation_system` already exists:
   - Click on it in the left sidebar
   - Click the "Operations" tab
   - Scroll down and click "Drop the database (DROP)"
   - Confirm the deletion

3. Click on the **Import** tab (at the top)

4. Click **Choose File** and select:
   ```
   d:\Xamp\htdocs\hotel-room-reservation\database.sql
   ```

5. Scroll down and click **Go** to import the database

   ✅ This will:
   - Create the `hotel_reservation_system` database
   - Create all necessary tables (users, rooms, reservations)
   - Insert sample data and default accounts

### Step 3: Configuration

1. Open `config/config.php` and verify database settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'hotel_reservation_system');
   ```

2. Update `APP_URL` if needed:
   ```php
   define('APP_URL', 'http://localhost/hotel-room-reservation');
   ```

### Step 4: Start the Application

1. Start XAMPP Apache and MySQL services

2. Open your browser and navigate to:
   ```
   http://localhost/hotel-room-reservation
   ```

## 🔐 Default Login Credentials

### Admin Account
- **Email:** `admin@hotel.com`
- **Password:** `admin123`
- **Access:** Full system management

### Test User Accounts
- **Email:** `john@example.com` | **Password:** `admin123`
- **Email:** `jane@example.com` | **Password:** `admin123`
- **Access:** User features only

## 📁 Project Structure

```
hotel-room-reservation/
├── admin/                      # Admin panel pages
│   ├── dashboard.php          # Admin dashboard with analytics
│   ├── reservations.php       # Manage reservations
│   ├── rooms.php              # Manage rooms (list)
│   ├── room_add.php           # Add/edit room form
│   ├── room_edit.php          # Room edit wrapper
│   └── users.php              # Manage users
├── config/                     # Configuration files
│   ├── config.php             # Database and app config
│   └── middleware.php         # Authentication middleware
├── includes/                   # Reusable components
│   ├── admin_nav.php          # Admin navigation bar
│   └── user_nav.php           # User navigation bar
├── user/                       # User panel pages
│   ├── dashboard.php          # User dashboard
│   ├── rooms.php              # Browse available rooms
│   ├── book.php               # Book a room
│   ├── reservations.php       # View reservations
│   ├── receipt.php            # View/download PDF receipt
│   └── profile.php            # User profile management
├── database.sql               # Database schema and seed data
├── migration_payment_features.sql  # Migration for payment features
├── index.php                  # Landing page
├── login.php                  # Login page
├── register.php               # Registration page
├── logout.php                 # Logout handler
└── README.md                  # This file
```

## 🗄️ Database Schema

### Tables

1. **users** - User accounts (users and admins)
   - id, name, email, password, phone, address, role, status, timestamps

2. **rooms** - Hotel rooms
   - id, room_number, room_type, capacity, price_per_night, description, amenities, image, status, timestamps

3. **reservations** - Booking records
   - id, user_id, room_id, check_in_date, check_out_date, number_of_guests, total_price, status, payment_status, payment_method, payment_date, checkout_date, admin_notes, timestamps

## 🎯 Key Features Explained

### Role-Based Access Control
- **Middleware system** authenticates and authorizes users
- Separate dashboards for users and admins
- Protected routes with automatic redirection

### Reservation Workflow
1. User browses available rooms
2. Selects dates and guest count
3. Submits booking request (status: pending)
4. Admin reviews and approves/rejects
5. User receives confirmation
6. User selects payment method (Online/Cash)
7. Payment is recorded in the system
8. User can view/download PDF receipt
9. User checks out when stay is complete (status: completed)

### Security Features
- Password hashing with bcrypt
- SQL injection prevention with prepared statements
- Session-based authentication
- XSS protection with input sanitization
- CSRF protection ready

## 🎨 Technology Stack

### Backend
- **PHP** - Pure PHP (no frameworks)
- **MySQL** - Database management
- **Session Management** - Secure authentication

### Frontend
- **HTML5** - Semantic markup
- **Tailwind CSS** - Utility-first styling
- **JavaScript** - Interactive features
- **Font Awesome** - Icon library
- **Chart.js** - Data visualization

### Design Principles
- **Mobile-First** - Responsive on all devices
- **Minimal Design** - Clean and modern interface
- **Accessibility** - Keyboard navigation and screen reader support
- **Performance** - Optimized images and fast load times

## 📱 Responsive Design

The application is fully responsive and works seamlessly on:
- 📱 Mobile devices (320px+)
- 📱 Tablets (768px+)
- 💻 Laptops (1024px+)
- 🖥️ Desktops (1280px+)

## 🔧 Customization

### Change Application Name
Edit `config/config.php`:
```php
define('APP_NAME', 'Your Hotel Name');
```

### Update Colors
The default color scheme uses Indigo. To change:
- Edit Tailwind classes in HTML files
- Replace `indigo-600`, `indigo-700` etc. with your preferred colors

### Add More Room Types
Edit the database and form options in:
- `database.sql` - ENUM values
- `admin/room_add.php` - Dropdown options
- `user/rooms.php` - Filter options

## 🐛 Troubleshooting

### Database Connection Error
- Verify MySQL is running in XAMPP
- Check database credentials in `config/config.php`
- Ensure database was imported correctly

### Page Not Found (404)
- Check that files are in correct htdocs folder
- Verify `APP_URL` in `config/config.php`
- Ensure Apache is running

### Images Not Loading
- Sample images use Unsplash URLs
- Check internet connection
- Or replace with local images

### Login Issues
- Clear browser cache and cookies
- Verify user exists in database
- Check password is `admin123` for test accounts

## 📊 Sample Data

The database includes:
- **1 Admin account**
- **2 User accounts**
- **12 Sample rooms** (various types)
- **5 Sample reservations** (different statuses)

## 🚀 Future Enhancements

Possible improvements:
- Email notifications for bookings
- Payment gateway integration
- Room availability calendar
- Guest reviews and ratings
- Multi-language support
- Advanced search filters
- Booking reports and exports

## 📄 License

This project is created for educational purposes. Feel free to use and modify as needed.

## 👨‍💻 Support

For issues or questions:
1. Check this README file
2. Verify database setup
3. Check PHP error logs in XAMPP
4. Ensure all files are properly uploaded

## 🚀 Getting Started

1. **Import the database** - Use phpMyAdmin to import `database.sql`
2. **If updating existing database** - Run `migration_payment_features.sql` to add payment features
3. **Login as admin** - Email: `admin@hotel.com`, Password: `admin123`
4. **Explore the dashboard** - View statistics and manage content
5. **Test user features** - Login as a regular user or create new account
6. **Make a booking** - Browse rooms and submit reservation
7. **Approve booking** - Switch to admin and approve the request
8. **Process payment** - Return to user account and select payment method
9. **View receipt** - Download PDF receipt for your records
10. **Check out** - Complete your stay and return the room

---

**Enjoy using Hotel Paradise Reservation System! 🏨✨**

For any customization needs or questions, refer to the well-commented code in each file.
