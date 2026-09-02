<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            ['Giám đốc',               'BGD',  'Director',  30000000, 25000000, 40000000, 'Đại diện pháp luật, điều hành toàn công ty.'],
            ['Trợ lý Giám đốc',        'BGD',  'Staff',     15000000, 12000000, 18000000, 'Hỗ trợ Giám đốc điều hành, lịch họp và công việc đối ngoại.'],
            ['Thư ký',                 'BGD',  'Staff',     12000000, 10000000, 15000000, 'Thư ký văn phòng Ban Giám đốc, soạn thảo và lưu trữ hồ sơ.'],
            ['Trưởng phòng HR',        'HR',   'Manager',   20000000, 18000000, 25000000, 'Quản lý toàn bộ hoạt động nhân sự.'],
            ['HR Manager',             'HR',   'Manager',   14000000, 12000000, 18000000, 'Điều phối nhân sự, chấm công, nghỉ phép.'],
            ['HR Executive',           'HR',   'Staff',     12000000, 10000000, 15000000, 'Phụ trách hồ sơ, hợp đồng và hành chính nhân sự.'],
            ['Recruitment Manager',    'TD',   'Manager',   18000000, 15000000, 22000000, 'Quản lý chiến lược tuyển dụng.'],
            ['Recruiter',              'TD',   'Staff',     10000000,  8000000, 13000000, 'Tìm kiếm và sàng lọc ứng viên.'],
            ['Talent Acquisition',     'TD',   'Staff',     11000000,  9000000, 14000000, 'Tìm kiếm nhân tài và xây dựng thương hiệu tuyển dụng.'],
            ['C&B Manager',            'CB',   'Manager',   18000000, 15000000, 22000000, 'Quản lý lương thưởng và phúc lợi.'],
            ['C&B Specialist',         'CB',   'Staff',     10000000,  8000000, 13000000, 'Tính lương, bảo hiểm và chế độ đãi ngộ.'],
            ['L&D Manager',            'DTPT', 'Manager',   18000000, 15000000, 22000000, 'Quản lý kế hoạch đào tạo và phát triển.'],
            ['Training Specialist',    'DTPT', 'Staff',     10000000,  8000000, 13000000, 'Tổ chức và đánh giá các khóa đào tạo.'],
            ['Kế toán trưởng',         'KTTC', 'Manager',   20000000, 18000000, 25000000, 'Quản lý bộ phận kế toán - tài chính.'],
            ['Accountant',             'KTTC', 'Staff',     12000000, 10000000, 15000000, 'Thực hiện công việc kế toán hằng ngày.'],
            ['Finance Manager',        'KTTC', 'Manager',   22000000, 18000000, 28000000, 'Quản lý dòng tiền và lập báo cáo tài chính.'],
            ['Sales Manager',          'KD',   'Manager',   22000000, 18000000, 28000000, 'Quản lý đội ngũ kinh doanh và doanh thu.'],
            ['Sales Executive',        'KD',   'Staff',     10000000,  8000000, 15000000, 'Tư vấn bán hàng và chăm sóc khách hàng.'],
            ['Sales Representative',   'KD',   'Staff',      9000000,  7000000, 12000000, 'Đại diện thương mại, tiếp thị sản phẩm.'],
            ['Marketing Manager',      'MKT',  'Manager',   20000000, 16000000, 25000000, 'Quản lý chiến dịch marketing.'],
            ['Marketing Executive',    'MKT',  'Staff',      9000000,  8000000, 12000000, 'Triển khai nội dung và chiến dịch quảng bá.'],
            ['IT Manager',             'IT',   'Manager',   25000000, 20000000, 30000000, 'Quản lý bộ phận công nghệ thông tin.'],
            ['System Admin',           'IT',   'Staff',     13000000, 10000000, 18000000, 'Quản trị hạ tầng, mạng và hệ thống.'],
            ['Developer',              'IT',   'Staff',     16000000, 13000000, 25000000, 'Phát triển phần mềm và hệ thống.'],
            ['Operations Manager',     'VH',   'Manager',   20000000, 16000000, 25000000, 'Quản lý vận hành chung của công ty.'],
            ['Operations Executive',   'VH',   'Staff',      9000000,  8000000, 12000000, 'Hỗ trợ vận hành hằng ngày.'],
            ['Legal Manager',          'PC',   'Manager',   22000000, 18000000, 28000000, 'Quản lý công tác pháp chế.'],
            ['Legal Specialist',       'PC',   'Staff',     12000000, 10000000, 15000000, 'Soạn thảo, rà soát hợp đồng và thủ tục pháp lý.'],
            ['Admin Manager',          'HC',   'Manager',   15000000, 12000000, 18000000, 'Quản lý hành chính văn phòng.'],
            ['Administrative Officer', 'HC',   'Staff',      9000000,  7000000, 12000000, 'Hỗ trợ hành chính, văn thư và hậu cần.'],
        ];

        foreach ($catalog as [$name, $code, $level, $base, $min, $max, $description]) {
            $department = $this->departmentByCode($code);
            if (! $department) {
                continue;
            }

            Position::updateOrCreate(['name' => $name], [
                'description' => $description,
                'level' => $level,
                'salary_range_min' => $min,
                'salary_range_max' => $max,
                'allowance' => (int) round($base / 12),
                'base_salary' => $base,
                'department_id' => $department->id,
            ]);
        }

        $this->normalizeBoardDirectorPositions();
    }

    /**
     * Ban Giám đốc giữ: Giám đốc, Trợ lý Giám đốc, Thư ký.
     */
    protected function normalizeBoardDirectorPositions(): void
    {
        $bgd = $this->departmentByCode('BGD');
        if (! $bgd) {
            return;
        }

        $keepNames = ['Giám đốc', 'Trợ lý Giám đốc', 'Thư ký'];
        $keep = [];

        foreach ([
            ['Giám đốc', 'Director', 30000000, 25000000, 40000000, 'Đại diện pháp luật, điều hành toàn công ty.'],
            ['Trợ lý Giám đốc', 'Staff', 15000000, 12000000, 18000000, 'Hỗ trợ Giám đốc điều hành, lịch họp và công việc đối ngoại.'],
            ['Thư ký', 'Staff', 12000000, 10000000, 15000000, 'Thư ký văn phòng Ban Giám đốc, soạn thảo và lưu trữ hồ sơ.'],
        ] as [$name, $level, $base, $min, $max, $description]) {
            $keep[$name] = Position::updateOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'level' => $level,
                    'salary_range_min' => $min,
                    'salary_range_max' => $max,
                    'allowance' => (int) round($base / 12),
                    'base_salary' => $base,
                    'department_id' => $bgd->id,
                ]
            );
        }

        $director = $keep['Giám đốc'];
        $obsolete = Position::query()
            ->where('department_id', $bgd->id)
            ->whereNotIn('name', $keepNames)
            ->get();

        foreach ($obsolete as $position) {
            \App\Models\Employee::query()
                ->where('position_id', $position->id)
                ->update([
                    'position_id' => $director->id,
                    'position' => 'Giám đốc',
                ]);

            if (\Illuminate\Support\Facades\Schema::hasTable('employee_position_histories')) {
                \App\Models\EmployeePositionHistory::query()
                    ->where('position_id', $position->id)
                    ->update(['position_id' => $director->id]);
            }

            $position->delete();
        }
    }

    protected function departmentByCode(string $code): ?Department
    {
        $aliases = [
            'HR' => ['HR', 'HCNS'],
            'IT' => ['IT', 'CNTT'],
            'KTTC' => ['KTTC', 'FINA'],
            'KD' => ['KD', 'SALE'],
            'DTPT' => ['DTPT', 'DT'],
        ];

        return Department::query()
            ->whereIn('code', $aliases[$code] ?? [$code])
            ->orderByRaw('CASE WHEN code = ? THEN 0 ELSE 1 END', [$code])
            ->first();
    }
}