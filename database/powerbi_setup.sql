-- ================================================================
-- Power BI Read-Only User Setup Script
-- PepsiCo Engineering CMMS
-- Database: cmmseng
-- Created: November 26, 2025
-- ================================================================

-- ----------------------------------------------------------------
-- STEP 1: Create Power BI Read-Only User
-- ----------------------------------------------------------------

-- Drop user if exists (for clean setup)
DROP USER IF EXISTS 'powerbi_readonly'@'%';
DROP USER IF EXISTS 'powerbi_readonly'@'localhost';

-- Create new user with strong password
-- IMPORTANT: Change 'YourSecurePassword123!' to a strong password
CREATE USER 'powerbi_readonly'@'%' IDENTIFIED BY 'YourSecurePassword123!';

-- Optional: Create user for local access only (more secure)
-- CREATE USER 'powerbi_readonly'@'localhost' IDENTIFIED BY 'YourSecurePassword123!';

-- Optional: Create user for specific IP address (most secure)
-- CREATE USER 'powerbi_readonly'@'192.168.1.100' IDENTIFIED BY 'YourSecurePassword123!';

-- ----------------------------------------------------------------
-- STEP 2: Grant SELECT Privileges on All Tables
-- ----------------------------------------------------------------

-- Grant SELECT on entire database (easiest option)
GRANT SELECT ON cmmseng.* TO 'powerbi_readonly'@'%';

-- ----------------------------------------------------------------
-- STEP 3: Grant SELECT on Specific Tables Only (More Secure)
-- ----------------------------------------------------------------
-- Uncomment this section if you want granular control

/*
-- Master Data Tables
GRANT SELECT ON cmmseng.areas TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.sub_areas TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.assets TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.sub_assets TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.parts TO 'powerbi_readonly'@'%';

-- Work Order Tables
GRANT SELECT ON cmmseng.work_orders TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.wo_processes TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.wo_parts_usage TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.wo_costs TO 'powerbi_readonly'@'%';

-- PM Tables
GRANT SELECT ON cmmseng.pm_schedules TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.pm_executions TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.pm_checklist_items TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.pm_parts_usage TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.pm_costs TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.pm_compliances TO 'powerbi_readonly'@'%';

-- Inventory Tables
GRANT SELECT ON cmmseng.inventories TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.inventory_movements TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.stock_alerts TO 'powerbi_readonly'@'%';

-- User Table (for technician performance)
GRANT SELECT ON cmmseng.users TO 'powerbi_readonly'@'%';

-- Other Tables
GRANT SELECT ON cmmseng.running_hours TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.barcode_tokens TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.activity_logs TO 'powerbi_readonly'@'%';
*/

-- ----------------------------------------------------------------
-- STEP 4: Apply Privileges
-- ----------------------------------------------------------------

FLUSH PRIVILEGES;

-- ----------------------------------------------------------------
-- STEP 5: Verify User Creation
-- ----------------------------------------------------------------

-- Check if user was created
SELECT 
    user, 
    host, 
    account_locked,
    password_expired
FROM mysql.user 
WHERE user = 'powerbi_readonly';

-- Check granted privileges
SHOW GRANTS FOR 'powerbi_readonly'@'%';

-- ----------------------------------------------------------------
-- STEP 6: Test Connection (Optional)
-- ----------------------------------------------------------------

-- Test query as powerbi_readonly user
-- Run this from a different connection with the new user:
-- mysql -u powerbi_readonly -p cmmseng

-- SELECT COUNT(*) FROM work_orders;
-- SELECT COUNT(*) FROM pm_executions;

-- ================================================================
-- SECURITY NOTES
-- ================================================================

-- 1. Password Security:
--    - Use minimum 16 characters
--    - Mix of uppercase, lowercase, numbers, symbols
--    - Never use default passwords in production
--    - Rotate password every 90 days

-- 2. Network Security:
--    - Prefer 'powerbi_readonly'@'SPECIFIC_IP' over '%'
--    - Use VPN for remote access
--    - Enable SSL for MySQL connections
--    - Keep MySQL port 3306 behind firewall

-- 3. Access Control:
--    - Grant SELECT only (never INSERT, UPDATE, DELETE)
--    - Review granted privileges regularly
--    - Monitor user activity via activity_logs
--    - Revoke access when no longer needed

-- 4. Firewall Rules:
--    sudo ufw allow from POWERBI_IP to any port 3306
--    sudo ufw enable

-- 5. MySQL Configuration:
--    - Edit /etc/mysql/mysql.conf.d/mysqld.cnf
--    - Set: bind-address = 0.0.0.0
--    - Restart: sudo systemctl restart mysql

-- ================================================================
-- PASSWORD ROTATION PROCEDURE
-- ================================================================

-- Every 90 days, change the password:
-- ALTER USER 'powerbi_readonly'@'%' IDENTIFIED BY 'NewSecurePassword456!';
-- FLUSH PRIVILEGES;

-- Then update credentials in:
-- 1. Power BI Desktop connection
-- 2. Power BI Service gateway
-- 3. Documentation (securely stored)

-- ================================================================
-- REVOKE ACCESS (When No Longer Needed)
-- ================================================================

-- REVOKE ALL PRIVILEGES ON cmmseng.* FROM 'powerbi_readonly'@'%';
-- DROP USER 'powerbi_readonly'@'%';
-- FLUSH PRIVILEGES;

-- ================================================================
-- END OF SCRIPT
-- ================================================================
