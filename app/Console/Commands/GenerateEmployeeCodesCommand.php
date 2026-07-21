<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Department;
use Illuminate\Console\Command;

class GenerateEmployeeCodesCommand extends Command
{
    protected $signature = 'employee:generate-codes';
    protected $description = 'Generate employee codes for employees without codes based on their department';

    public function handle(): int
    {
        $employeesWithoutCodes = Employee::whereNull('employee_code')->orWhere('employee_code', '')->get();
        
        if ($employeesWithoutCodes->isEmpty()) {
            $this->info('✓ All employees already have codes!');
            return 0;
        }

        $this->info("📝 Generating codes for {$employeesWithoutCodes->count()} employees...\n");

        $count = 0;
        foreach ($employeesWithoutCodes as $employee) {
            if (!$employee->department_id) {
                $this->warn("⚠️  Skipped: {$employee->name} (no department assigned)");
                continue;
            }

            try {
                $department = Department::find($employee->department_id);
                if (!$department) {
                    $this->warn("⚠️  Skipped: {$employee->name} (department not found)");
                    continue;
                }

                $code = Employee::generateUniqueEmployeeCode($department);
                $employee->update(['employee_code' => $code]);
                
                $this->info("✓ {$employee->name}: {$code}");
                $count++;
            } catch (\Exception $e) {
                $this->error("✗ Failed for {$employee->name}: {$e->getMessage()}");
            }
        }

        $this->info("\n✓ Generated codes for {$count} employees!");
        return 0;
    }
}
