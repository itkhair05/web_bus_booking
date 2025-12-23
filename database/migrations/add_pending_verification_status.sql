-- Migration: Add pending_verification payment status
-- Date: 2025-12-17
-- Description: Add 'pending_verification' value to payment_status enum for sandbox payment verification

-- Update the payment_status enum in bookings table to include 'pending_verification' and 'pending'
ALTER TABLE `bookings` 
MODIFY COLUMN `payment_status` ENUM('unpaid', 'pending', 'pending_verification', 'paid', 'refunded', 'failed') 
NOT NULL DEFAULT 'unpaid';

-- Also update payments table status if needed
ALTER TABLE `payments`
MODIFY COLUMN `status` ENUM('pending', 'pending_verification', 'success', 'failed', 'refunded', 'cancelled')
NOT NULL DEFAULT 'pending';

-- Add index for faster payment status queries
ALTER TABLE `bookings` ADD INDEX `idx_payment_status` (`payment_status`);

-- Optional: Add column for verification tracking
-- ALTER TABLE `bookings` ADD COLUMN `verified_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;
-- ALTER TABLE `bookings` ADD COLUMN `verified_by` INT NULL DEFAULT NULL AFTER `verified_at`;

