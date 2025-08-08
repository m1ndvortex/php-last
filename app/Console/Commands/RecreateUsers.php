<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RecreateUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:recreate {--fresh : Run fresh migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recreate users table and insert default users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting user recreation process...');

        // Run fresh migrations if requested
        if ($this->option('fresh')) {
            $this->info('🔄 Running fresh migrations...');
            $this->call('migrate:fresh', ['--force' => true]);
        }

        // Check if users table exists and has data
        try {
            $userCount = User::count();
            if ($userCount > 0) {
                if (!$this->confirm("Users table already has {$userCount} users. Do you want to continue and add more users?")) {
                    $this->info('Operation cancelled.');
                    return 0;
                }
            }
        } catch (\Exception $e) {
            $this->error('Users table might not exist. Running migrations...');
            $this->call('migrate', ['--force' => true]);
        }

        $this->info('👤 Creating default users...');

        // Create default users
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@jewelry.local',
                'password' => 'admin123',
                'role' => 'owner',
                'preferred_language' => 'en',
            ],
            [
                'name' => 'Manager User',
                'email' => 'manager@jewelry.local',
                'password' => 'manager123',
                'role' => 'manager',
                'preferred_language' => 'en',
            ],
            [
                'name' => 'Employee User',
                'email' => 'employee@jewelry.local',
                'password' => 'employee123',
                'role' => 'employee',
                'preferred_language' => 'en',
            ],
            [
                'name' => 'کاربر فارسی',
                'email' => 'persian@jewelry.local',
                'password' => 'persian123',
                'role' => 'owner',
                'preferred_language' => 'fa',
            ],
        ];

        $createdUsers = [];
        $skippedUsers = [];

        foreach ($users as $userData) {
            // Check if user already exists
            $existingUser = User::where('email', $userData['email'])->first();
            
            if ($existingUser) {
                $skippedUsers[] = $userData['email'];
                $this->warn("⚠️  User {$userData['email']} already exists, skipping...");
                continue;
            }

            try {
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password']),
                    'role' => $userData['role'],
                    'preferred_language' => $userData['preferred_language'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);

                $createdUsers[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'language' => $user->preferred_language,
                ];

                $this->info("✅ Created user: {$userData['email']}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to create user {$userData['email']}: " . $e->getMessage());
            }
        }

        // Run seeders
        $this->info('🌱 Running database seeders...');
        try {
            $this->call('db:seed', ['--force' => true]);
        } catch (\Exception $e) {
            $this->warn('⚠️  Seeding failed or no seeders found: ' . $e->getMessage());
        }

        // Display results
        $this->info('📋 User Creation Summary:');
        
        if (!empty($createdUsers)) {
            $this->table(
                ['ID', 'Name', 'Email', 'Role', 'Language'],
                $createdUsers
            );
        }

        if (!empty($skippedUsers)) {
            $this->warn('⚠️  Skipped existing users: ' . implode(', ', $skippedUsers));
        }

        $this->info('✅ User recreation completed successfully!');
        $this->newLine();
        $this->info('🔑 Default Login Credentials:');
        $this->line('   Admin:    admin@jewelry.local / admin123');
        $this->line('   Manager:  manager@jewelry.local / manager123');
        $this->line('   Employee: employee@jewelry.local / employee123');
        $this->line('   Persian:  persian@jewelry.local / persian123');
        $this->newLine();
        $this->info('🌐 You can now access the application at http://localhost');

        return 0;
    }
}