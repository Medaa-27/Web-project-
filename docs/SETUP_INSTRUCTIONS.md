# Property Review System Setup Instructions

## ⚠️ IMPORTANT: Database Setup Required

The Property Review System requires database updates to function properly. Currently, the details modal is not working because the necessary database columns don't exist yet.

## 🔧 Quick Fix Steps

### Option 1: Run SQL Script (Recommended)

1. **Open phpMyAdmin** (usually at http://localhost/phpmyadmin)
2. **Select your database** (aksum_rental_db)
3. **Click on "SQL" tab**
4. **Copy and paste** the following SQL script:

```sql
-- Property Review System for Aksum House Rental Management System
-- This script adds the necessary tables and columns for house registration review

-- Add review_status column to properties table
ALTER TABLE properties 
ADD COLUMN review_status ENUM('pending', 'approved', 'rejected', 'needs_revision') NOT NULL DEFAULT 'pending' AFTER status,
ADD COLUMN reviewed_by INT NULL AFTER review_status,
ADD COLUMN review_date TIMESTAMP NULL AFTER reviewed_by,
ADD COLUMN review_comments TEXT NULL AFTER review_date;

-- Create property_reviews table for detailed review history
CREATE TABLE IF NOT EXISTS property_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    employee_id INT NOT NULL,
    review_status ENUM('pending', 'approved', 'rejected', 'needs_revision') NOT NULL,
    review_comments TEXT NULL,
    rejection_reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_property_review (property_id),
    INDEX idx_employee_review (employee_id),
    INDEX idx_review_status (review_status)
);

-- Create notifications for property owners about review status
CREATE TABLE IF NOT EXISTS property_review_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    owner_id INT NOT NULL,
    employee_id INT NOT NULL,
    notification_type ENUM('approved', 'rejected', 'needs_revision') NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_property_notification (property_id),
    INDEX idx_owner_notification (owner_id),
    INDEX idx_is_read (is_read)
);

-- Update existing properties to have 'approved' status (assuming they were already reviewed)
UPDATE properties SET review_status = 'approved' WHERE status IN ('available', 'rented', 'maintenance');

-- Add indexes for better performance
CREATE INDEX idx_properties_review_status ON properties(review_status);
CREATE INDEX idx_properties_reviewed_by ON properties(reviewed_by);
```

5. **Click "Go"** to execute the script
6. **Verify success** - you should see "Query executed successfully" messages

### Option 2: Import SQL File

1. **Download the SQL file**: `../database/create_property_review_system.sql`
2. **Open phpMyAdmin**
3. **Select your database**
4. **Click "Import" tab**
5. **Choose the SQL file** from your computer
6. **Click "Go"** to import

## ✅ After Setup

Once you've run the SQL script:

1. **Refresh the Property Review page** - Details modal should now work
2. **Test the review functionality** - Approve/Reject/Request Revision buttons
3. **Check the dashboard** - Statistics should display correctly
4. **Verify sidebar counts** - Pending review count should update

## 🔍 Verification

To verify the setup worked:

1. **Check properties table structure**:
   ```sql
   DESCRIBE properties;
   ```
   You should see: `review_status`, `reviewed_by`, `review_date`, `review_comments`

2. **Check new tables exist**:
   ```sql
   SHOW TABLES LIKE 'property_reviews';
   SHOW TABLES LIKE 'property_review_notifications';
   ```

3. **Test the Property Review page**:
   - Go to Employee Dashboard → Property Review
   - Click "View Details" on any property
   - Modal should display all property information correctly

## 🚨 Troubleshooting

### If details modal still doesn't work:

1. **Check browser console** for JavaScript errors (F12 → Console)
2. **Verify database columns** exist using phpMyAdmin
3. **Check PHP error logs** in XAMPP logs
4. **Ensure Bootstrap 5** is loading properly

### Common Issues:

- **"Column not found" errors**: SQL script wasn't executed properly
- **Modal doesn't open**: JavaScript/Bootstrap loading issue
- **Empty data**: No properties in database or wrong permissions

## 📞 Support

If you encounter issues:
1. Check that all SQL commands executed successfully
2. Verify database permissions
3. Ensure XAMPP/Apache is restarted after changes

---

**⚡ Quick Test**: After running SQL, visit the Property Review page and click "View Details" - it should work immediately!
