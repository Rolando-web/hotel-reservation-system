# Quick Setup Instructions - Payment Features Update

## If You Already Have the Database Installed:

### Option 1: Run Migration (Recommended)
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click on `hotel_reservation_system` database
3. Click the "SQL" tab at the top
4. Copy and paste the contents of `migration_payment_features.sql`
5. Click "Go" to execute
6. ✅ Payment features are now enabled!

### Option 2: Fresh Database Import
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click on `hotel_reservation_system` database
3. Click "Operations" tab
4. Click "Drop the database (DROP)" and confirm
5. Click "Import" tab at the top
6. Choose `database.sql` file
7. Click "Go"
8. ✅ Fresh database with all features installed!

## New Features Added:

✅ **Payment System**
- Online payment option
- Cash payment option
- Payment tracking and status

✅ **Check Out / Return Room**
- Mark reservations as completed
- Track checkout dates

✅ **PDF Receipt**
- View receipt in browser
- Print receipt
- Download as PDF
- Professional invoice layout
- Payment details included

## How to Use:

1. **After Admin Approves** → Booking status changes to "Approved"
2. **Click "Pay Now"** → Select payment method (Online/Cash)
3. **Payment Confirmed** → "View Receipt" button appears
4. **Click "View Receipt"** → Opens professional receipt page
5. **Download/Print** → Use buttons to save or print receipt
6. **Click "Check Out"** → Return room when stay is complete
7. **Status Changes** → Booking marked as "Completed"

## Reservation Status Flow:

1. **Pending** → Waiting for admin approval (can cancel)
2. **Approved** → Admin approved (can pay)
3. **Paid** → Payment completed (can view receipt & check out)
4. **Completed** → Stay finished (can view receipt)
5. **Cancelled** → User cancelled
6. **Rejected** → Admin rejected

## Test Credentials:

**Admin:** admin@hotel.com / admin123  
**User:** john@example.com / admin123

---

💡 **Tip:** The first approved reservation in the sample data is already marked as paid so you can test the receipt feature immediately!

🎉 **Ready to go!** Refresh your reservations page to see the new features.
