<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DemoEmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target = 25;
        $existing = Employee::count();
        $toCreate = max(0, $target - $existing);

        if ($toCreate === 0) {
            $this->command->info("Employees already >= {$target}, skipping DemoEmployeeSeeder.");
            return;
        }

        $departmentIds = Department::pluck('id')->all();
        if (empty($departmentIds)) {
            $this->command->warn('No departments found; cannot assign department to demo employees.');
            return;
        }

        $positions = ['Developer','HR Specialist','Sales Executive','Accountant','Marketing Manager','Support Engineer'];

        for ($i = 0; $i < $toCreate; $i++) {
            $user = User::factory()->create();

            $name = $user->name;
            $email = $user->email;
            $empCode = 'EMP' . strtoupper(Str::random(5));
            $gender = ['male','female'][array_rand([0,1])];
            $dob = Carbon::now()->subYears(rand(25,40))->subDays(rand(0,365))->toDateString();
            $start = Carbon::now()->subYears(rand(0,6))->subDays(rand(0,365))->toDateString();
            $department_id = $departmentIds[array_rand($departmentIds)];
            $position = $positions[array_rand($positions)];

            Employee::create([
                'user_id' => $user->id,
                'employee_code' => $empCode,
                'name' => $name,
                'email' => $email,
                'position' => $position,
                'department_id' => $department_id,
                'status' => 'active',
                'gender' => $gender,
                'dob' => $dob,
                'phone' => '09' . rand(10000000,99999999),
                'address' => 'Demo address',
                'start_date' => $start,
                'leave_balance' => rand(5,20),
            ]);
        }

        $this->command->info("Created {$toCreate} demo employees (target {$target}).");
    }
}
