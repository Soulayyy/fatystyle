<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $name = trim((string) env('ADMIN_BOOTSTRAP_NAME'));
        $email = Str::lower(trim((string) env('ADMIN_BOOTSTRAP_EMAIL')));
        $password = (string) env('ADMIN_BOOTSTRAP_PASSWORD');

        if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && mb_strlen($password) >= 14) {
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );

            if ($user->wasRecentlyCreated) {
                $user->syncRoles([RoleName::SuperAdministrator->value]);
            }
        }
    }
}
