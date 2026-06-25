<?php
/*
================================================================
NCR-CET IT Lab — Complete Feature Setup Guide
================================================================

STEP 1: SQL run karo (phpMyAdmin mein new_tables.sql)
STEP 2: Yeh files new_dashboard/ folder mein copy karo:
  - asset_management.php
  - software_licenses.php
  - backup_tracker.php
  - alerts.php
  - active_users.php
  - reports.php

STEP 3: header.php mein sidebar mein yeh links add karo:

<a href="asset_management.php" class="nav-link <?= ($_GET['view']??'')==='assets'?'active':'' ?>">
  <i class="ti ti-box"></i><span>Assets & Maintenance</span>
</a>
<a href="software_licenses.php" class="nav-link <?= ($_GET['view']??'')==='licenses'?'active':'' ?>">
  <i class="ti ti-certificate"></i><span>Software Licenses</span>
</a>
<a href="backup_tracker.php" class="nav-link <?= ($_GET['view']??'')==='backup'?'active':'' ?>">
  <i class="ti ti-database"></i><span>Backup Tracker</span>
</a>
<a href="alerts.php" class="nav-link <?= ($_GET['view']??'')==='alerts'?'active':'' ?>">
  <i class="ti ti-bell"></i><span>Alerts</span>
</a>
<a href="active_users.php" class="nav-link <?= ($_GET['view']??'')==='active_users'?'active':'' ?>">
  <i class="ti ti-users"></i><span>Active Users</span>
</a>
<a href="reports.php" class="nav-link <?= ($_GET['view']??'')==='reports'?'active':'' ?>">
  <i class="ti ti-chart-bar"></i><span>Reports</span>
</a>
<a href="print_quota.php" class="nav-link <?= ($_GET['view']??'')==='print_quota'?'active':'' ?>">
  <i class="ti ti-printer"></i><span>Print Quota</span>
</a>

================================================================
FEATURE SUMMARY
================================================================

1. asset_management.php  — 3 tabs:
   - Assets        : PC/laptop/device register, warranty, vendor, price
   - Maintenance   : schedule, status, cost, overdue alerts
   - Peripherals   : printers, projectors, switches etc.

2. software_licenses.php — license tracker:
   - Total/used count, expiry, over-usage warning
   - Per device / per user / site license types

3. backup_tracker.php    — backup log:
   - Success/Failed/Partial status
   - PCs with no backup in last 7 days counter

4. alerts.php            — notification center:
   - Auto-generates alerts from all modules
   - Warranty expiry, maintenance overdue,
     license expiry, print quota exceeded
   - Mark read / delete

5. active_users.php      — user activity:
   - Who is online right now
   - Full activity history with filter

6. reports.php           — analytics & export:
   - System summary dashboard
   - PC category distribution bar
   - CSV export for all 6 modules

7. print_quota.php       — (already done earlier)
   - Per user print limit
   - Manual reset by admin

================================================================
DATABASE TABLES CREATED (new_tables.sql):
================================================================
- asset_management
- maintenance_schedule
- peripherals
- software_licenses
- backup_logs
- system_alerts
(print quota tables were in separate print_quota_tables.sql)

================================================================
*/
?>