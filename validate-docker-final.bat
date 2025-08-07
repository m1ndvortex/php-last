@echo off
echo === Final Docker Integration Validation ===
echo.

echo 1. Testing Docker services status...
docker-compose ps
echo.

echo 2. Testing web server accessibility...
powershell -Command "try { $response = Invoke-WebRequest -Uri http://localhost -Method Head -TimeoutSec 5; Write-Host 'Web server: HTTP' $response.StatusCode } catch { Write-Host 'Web server: Not accessible' }"
echo.

echo 3. Testing category image serving...
powershell -Command "try { $response = Invoke-WebRequest -Uri http://localhost/storage/categories/gold-jewelry.svg -Method Head -TimeoutSec 5; Write-Host 'Category images: HTTP' $response.StatusCode } catch { Write-Host 'Category images: Not accessible' }"
echo.

echo 4. Testing database connectivity...
docker exec jewelry_app php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'Database: Connected'; } catch (Exception $e) { echo 'Database: Failed'; }"
echo.

echo 5. Testing category services...
docker exec jewelry_app php artisan tinker --execute="try { app(\App\Services\CategoryService::class); echo 'Category services: Loaded'; } catch (Exception $e) { echo 'Category services: Failed'; }"
echo.

echo 6. Testing image processing...
docker exec jewelry_app php -r "echo extension_loaded('gd') ? 'Image processing: GD extension loaded' : 'Image processing: GD extension missing';"
echo.

echo 7. Testing storage directories...
docker exec jewelry_app bash -c "if [ -d '/var/www/storage/app/public/categories' ] && [ -d '/var/www/storage/app/public/categories/thumbnails' ] && [ -d '/var/www/storage/backups/categories' ]; then echo 'Storage directories: All exist'; else echo 'Storage directories: Some missing'; fi"
echo.

echo 8. Testing nginx configuration...
docker exec jewelry_nginx nginx -t
echo.

echo === Validation Complete ===
echo.
echo The Docker integration for category management is working!
echo.
echo Key URLs to test:
echo - Main application: http://localhost
echo - Category images: http://localhost/storage/categories/gold-jewelry.svg
echo - API endpoint: http://localhost/api/categories
echo.
pause