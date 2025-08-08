#!/bin/bash

# Recreate Users Table and Insert Default Admin User
# This script will recreate the users table and insert a default admin user

set -e

echo "🔄 Starting user table recreation process..."

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
until docker exec jewelry_mysql mysqladmin ping -h"localhost" -u"root" -p"root_password" --silent; do
    echo "Waiting for MySQL..."
    sleep 2
done

echo "✅ MySQL is ready!"

# Run Laravel migrations to recreate users table
echo "🔄 Running Laravel migrations..."
docker exec jewelry_app php artisan migrate:fresh --force

# Create default admin user
echo "👤 Creating default admin user..."
docker exec jewelry_app php artisan tinker --execute="
\$user = new App\Models\User();
\$user->name = 'Admin User';
\$user->email = 'admin@jewelry.local';
\$user->password = Hash::make('admin123');
\$user->preferred_language = 'en';
\$user->role = 'owner';
\$user->is_active = true;
\$user->email_verified_at = now();
\$user->save();
echo 'Admin user created successfully!';
"

# Create additional test users
echo "👥 Creating additional test users..."
docker exec jewelry_app php artisan tinker --execute="
// Manager User
\$manager = new App\Models\User();
\$manager->name = 'Manager User';
\$manager->email = 'manager@jewelry.local';
\$manager->password = Hash::make('manager123');
\$manager->preferred_language = 'en';
\$manager->role = 'manager';
\$manager->is_active = true;
\$manager->email_verified_at = now();
\$manager->save();

// Employee User
\$employee = new App\Models\User();
\$employee->name = 'Employee User';
\$employee->email = 'employee@jewelry.local';
\$employee->password = Hash::make('employee123');
\$employee->preferred_language = 'en';
\$employee->role = 'employee';
\$employee->is_active = true;
\$employee->email_verified_at = now();
\$employee->save();

// Persian User
\$persian = new App\Models\User();
\$persian->name = 'کاربر فارسی';
\$persian->email = 'persian@jewelry.local';
\$persian->password = Hash::make('persian123');
\$persian->preferred_language = 'fa';
\$persian->role = 'owner';
\$persian->is_active = true;
\$persian->email_verified_at = now();
\$persian->save();

echo 'Additional test users created successfully!';
"

# Run seeders if they exist
echo "🌱 Running database seeders..."
docker exec jewelry_app php artisan db:seed --force || echo "⚠️  No seeders found or seeding failed"

# Display created users
echo "📋 Displaying created users..."
docker exec jewelry_app php artisan tinker --execute="
\$users = App\Models\User::all(['id', 'name', 'email', 'role', 'preferred_language', 'is_active']);
foreach (\$users as \$user) {
    echo \"ID: {\$user->id} | Name: {\$user->name} | Email: {\$user->email} | Role: {\$user->role} | Language: {\$user->preferred_language} | Active: \" . (\$user->is_active ? 'Yes' : 'No') . \"\n\";
}
"

echo "✅ User table recreation completed successfully!"
echo ""
echo "🔑 Default Login Credentials:"
echo "   Admin:    admin@jewelry.local / admin123"
echo "   Manager:  manager@jewelry.local / manager123"
echo "   Employee: employee@jewelry.local / employee123"
echo "   Persian:  persian@jewelry.local / persian123"
echo ""
echo "🌐 You can now access the application at http://localhost"