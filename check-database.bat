@echo off
echo 🔍 Checking database status...
echo.

echo 📊 MySQL Container Status:
docker ps --filter "name=jewelry_mysql" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo.

echo 🗄️ Database Connection Test:
docker exec jewelry_mysql mysqladmin ping -h"localhost" -u"root" -p"root_password" --silent
if errorlevel 1 (
    echo ❌ MySQL is not responding
) else (
    echo ✅ MySQL is responding
)
echo.

echo 📋 Current Tables:
docker exec jewelry_mysql mysql -u"root" -p"root_password" -D"jewelry_platform" -e "SHOW TABLES;"
echo.

echo 👥 Users Table Status:
docker exec jewelry_mysql mysql -u"root" -p"root_password" -D"jewelry_platform" -e "SELECT COUNT(*) as user_count FROM users;" 2>nul
if errorlevel 1 (
    echo ❌ Users table does not exist or is empty
) else (
    echo ✅ Users table exists
    echo.
    echo 📋 Current Users:
    docker exec jewelry_mysql mysql -u"root" -p"root_password" -D"jewelry_platform" -e "SELECT id, name, email, role, preferred_language, is_active FROM users;"
)

pause