<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            ['name' => 'Giám đốc', 'description' => 'Giám đốc điều hành công ty', 'level' => 'C-level', 'salary_range_min' => 30000000, 'salary_range_max' => 50000000, 'allowance' => 5000000, 'base_salary' => 40000000],
            ['name' => 'Phó Giám đốc', 'description' => 'Phó giám đốc phụ trách bộ phận', 'level' => 'Director', 'salary_range_min' => 25000000, 'salary_range_max' => 35000000, 'allowance' => 3000000, 'base_salary' => 30000000],
            ['name' => 'Trưởng phòng Nhân sự', 'description' => 'Quản lý phòng nhân sự', 'level' => 'Manager', 'salary_range_min' => 18000000, 'salary_range_max' => 25000000, 'allowance' => 2000000, 'base_salary' => 20000000],
            ['name' => 'Trưởng phòng Kế toán', 'description' => 'Quản lý phòng kế toán', 'level' => 'Manager', 'salary_range_min' => 18000000, 'salary_range_max' => 25000000, 'allowance' => 2000000, 'base_salary' => 20000000],
            ['name' => 'Trưởng phòng IT', 'description' => 'Quản lý phòng công nghệ thông tin', 'level' => 'Manager', 'salary_range_min' => 20000000, 'salary_range_max' => 30000000, 'allowance' => 2000000, 'base_salary' => 25000000],
            ['name' => 'Trưởng phòng Kinh doanh', 'description' => 'Quản lý phòng kinh doanh', 'level' => 'Manager', 'salary_range_min' => 18000000, 'salary_range_max' => 28000000, 'allowance' => 2000000, 'base_salary' => 22000000],
            ['name' => 'Chuyên viên Nhân sự', 'description' => 'Chuyên viên tuyển dụng và hồ sơ', 'level' => 'Staff', 'salary_range_min' => 10000000, 'salary_range_max' => 15000000, 'allowance' => 1000000, 'base_salary' => 12000000],
            ['name' => 'Kế toán viên', 'description' => 'Thực hiện công việc kế toán', 'level' => 'Staff', 'salary_range_min' => 10000000, 'salary_range_max' => 15000000, 'allowance' => 1000000, 'base_salary' => 12000000],
            ['name' => 'Backend Developer', 'description' => 'Phát triển hệ thống Backend', 'level' => 'Staff', 'salary_range_min' => 15000000, 'salary_range_max' => 25000000, 'allowance' => 1500000, 'base_salary' => 18000000],
            ['name' => 'Frontend Developer', 'description' => 'Phát triển giao diện Frontend', 'level' => 'Staff', 'salary_range_min' => 13000000, 'salary_range_max' => 22000000, 'allowance' => 1500000, 'base_salary' => 16000000],
            ['name' => 'Nhân viên Kinh doanh', 'description' => 'Nhân viên phòng kinh doanh', 'level' => 'Staff', 'salary_range_min' => 8000000, 'salary_range_max' => 15000000, 'allowance' => 800000, 'base_salary' => 10000000],
            ['name' => 'Nhân viên Văn phòng', 'description' => 'Nhân viên hành chính văn phòng', 'level' => 'Staff', 'salary_range_min' => 7000000, 'salary_range_max' => 12000000, 'allowance' => 500000, 'base_salary' => 9000000],
            ['name' => 'Thực tập sinh', 'description' => 'Nhân viên thực tập', 'level' => 'Intern', 'salary_range_min' => 3000000, 'salary_range_max' => 5000000, 'allowance' => 0, 'base_salary' => 4000000],
        ];

        foreach ($positions as $position) {
            Position::updateOrCreate(
                ['name' => $position['name']],
                $position
            );
        }
    }
}
