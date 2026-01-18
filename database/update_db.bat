@echo off
cd "c:\xampp\mysql\bin"
mysql -u root -p agrivision_db < "c:\xampp\htdocs\AGRIVISION 4\AGRIVISION\database\update_tables.sql"
echo Database tables updated successfully!
pause
