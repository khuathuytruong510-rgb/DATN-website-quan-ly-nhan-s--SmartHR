<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FillDepartmentStaffSeeder extends Seeder
{
    public const PASSWORD = '123456';

    public const TARGET_PER_DEPARTMENT = 6;

    private array $lastNames = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý'];

    private array $maleNames = ['Minh', 'Anh', 'Đức', 'Tuấn', 'Hùng', 'Quân', 'Long', 'Thắng', 'Hiếu', 'Phúc', 'Cường', 'Dũng', 'Bảo', 'Sơn', 'Trung', 'Kiên', 'Vinh', 'Huy', 'Khang', 'Phát'];

    private array $femaleNames = ['Hương', 'Linh', 'Mai', 'Lan', 'Trang', 'Thùy', 'Ngọc', 'Thảo', 'Thu', 'Hà', 'Nga', 'Phương', 'Quỳnh', 'Vy', 'Hằng', 'Yến', 'Nhi', 'Giang', 'Dung', 'Ánh'];

    private array $maleMiddle = ['Văn', 'Đình', 'Hữu', 'Mạnh', 'Xuân', 'Công', 'Quốc'];

    private array $femaleMiddle = ['Thị', 'Ngọc', 'Thanh', 'Thu', 'Kim', 'Bích', 'Mỹ'];

    public function run(): void
    {
        $created = 0;
        $fullDepartments = [];

        Position::updateOrCreate(['name' => 'Trợ lý Giám đốc'], [
            'department_id' => optional(Department::where('code', 'BGD')->first())->id,
            'description' => 'Hỗ trợ Ban Giám đốc trong công tác điều hành.',
            'level' => 'C1',
            'salary_range_min' => 9000000,
            'salary_range_max' => 15000000,
            'base_salary' => 12000000,
            'allowance' => 1000000,
        ]);

        foreach (Department::orderBy('code')->get() as $dept) {
            $current = Employee::where('department_id', $dept->id)->count();
            $need = max(0, self::TARGET_PER_DEPARTMENT - $current);

            if ($need === 0) {
                $fullDepartments[] = $dept->code;
                continue;
            }

            $slots = $dept->code === 'BGD'
                ? $this->bgdSlots($need)
                : $this->positionSlots($dept, $need);

            $seq = $current + 1;
            foreach ($slots as $slot) {
                $this->create($dept, $slot['position_id'], $slot['position'], $seq++);
                $created++;
            }
        }

        $this->command?->info("Đã tạo {$created} nhân viên mới. Mật khẩu tài khoản: ".self::PASSWORD);
        if ($fullDepartments) {
            $this->command?->info('Phòng ban đã đủ người: '.implode(', ', $fullDepartments));
        }
    }

    private function positionSlots(Department $dept, int $need): array
    {
        $positions = Position::where('department_id', $dept->id)
            ->orderBy('base_salary', 'desc')
            ->get();

        if ($positions->isEmpty()) {
            $positions = collect([['id' => null, 'name' => 'Nhân viên']]);
        }

        $slots = [];
        for ($i = 0; $i < $need; $i++) {
            $p = $positions->get($i % $positions->count());
            $slots[] = ['position_id' => $p instanceof Position ? $p->id : null, 'position' => $p['name'] ?? 'Nhân viên'];
        }

        return $slots;
    }

    private function bgdSlots(int $need): array
    {
        $gd = Position::where('name', 'Tổng Giám đốc')->first();
        $pgd = Position::where('name', 'Phó Tổng Giám đốc')->first();
        $troly = Position::where('name', 'Trợ lý Giám đốc')->first();

        $slots = [];
        if ($gd) {
            $slots[] = ['position_id' => $gd->id, 'position' => $gd->name];
        }
        foreach (range(1, 2) as $i) {
            if ($pgd) {
                $slots[] = ['position_id' => $pgd->id, 'position' => $pgd->name];
            }
        }
        while (count($slots) < $need) {
            if ($troly) {
                $slots[] = ['position_id' => $troly->id, 'position' => $troly->name];
            } else {
                $slots[] = ['position_id' => null, 'position' => 'Nhân viên'];
            }
        }

        return array_slice($slots, 0, $need);
    }

    private function create(Department $dept, ?int $positionId, string $position, int $seq): void
    {
        $email = strtolower($dept->code).'-'.str_pad($seq, 3, '0', STR_PAD_LEFT).'@smarthr.com';

        $user = User::updateOrCreate(['email' => $email], [
            'name' => '',
            'password' => Hash::make(self::PASSWORD),
            'api_token' => Str::random(60),
            'is_admin' => false,
            'is_hr' => false,
            'is_accountant' => false,
            'is_director' => false,
            'is_locked' => false,
        ]);

        $profile = $this->profile();

        $employeeCode = $this->uniqueCode($dept, $seq);

        Employee::updateOrCreate(['email' => $email], array_merge([
            'user_id' => $user->id,
            'name' => $profile['name'],
            'position' => $position,
            'position_id' => $positionId,
            'department_id' => $dept->id,
            'status' => 'active',
            'employee_code' => $employeeCode,
            'start_date' => Carbon::now()->subYears(rand(1, 4))->subDays(rand(0, 300))->toDateString(),
            'leave_balance' => 12,
            'bank_name' => ['Vietcombank', 'MB Bank', 'Techcombank', 'BIDV', 'VPBank', 'Agribank'][array_rand(['Vietcombank', 'MB Bank', 'Techcombank', 'BIDV', 'VPBank', 'Agribank'])],
            'account_number' => (string) rand(100000000000, 999999999999),
        ], $profile['profile']));

        $user->update(['name' => $profile['name']]);
    }

    private function profile(): array
    {
        $female = (bool) random_int(0, 1);
        $fullName = $this->fullName($female);

        $gender = $female ? 'female' : 'male';
        $dob = Carbon::now()->subYears(random_int(1990, 2001))->toDateString();
        $phone = '0'.random_int(3, 9).random_int(10000000, 99999999);

        $addresses = [
            'Số 12, Nguyễn Trãi, Thanh Xuân, Hà Nội',
            'Số 45, Hoàng Quốc Việt, Cầu Giấy, Hà Nội',
            'Số 78, Lạc Long Quân, Tây Hồ, Hà Nội',
            'Số 23, Điện Biên Phủ, Hải Châu, Đà Nẵng',
            'Số 56, Lê Duẩn, Hải Châu, Đà Nẵng',
            'Số 89, Cách Mạng Tháng 8, Tân Bình, TP. Hồ Chí Minh',
            'Số 102, Nguyễn Văn Linh, Hải Châu, Đà Nẵng',
            'Số 34, Trần Hưng Đạo, Hoàn Kiếm, Hà Nội',
        ];

        return [
            'name' => $fullName,
            'profile' => [
                'gender' => $gender,
                'dob' => $dob,
                'cccd' => (string) rand(100000000000, 999999999999),
                'phone' => $phone,
                'address' => $addresses[array_rand($addresses)],
                'address_detail' => 'Phòng '.random_int(101, 909).' - Tầng '.random_int(1, 20),
                'education' => ['Trung cấp', 'Cao đẳng', 'Đại học', 'Đại học', 'Đại học', 'Thạc sĩ'][array_rand(['Trung cấp', 'Cao đẳng', 'Đại học', 'Đại học', 'Đại học', 'Thạc sĩ'])],
                'experience' => random_int(1, 15).' năm',
            ],
        ];
    }

    private function fullName(bool $female): string
    {
        $last = $this->lastNames[array_rand($this->lastNames)];
        $middle = $female
            ? $this->femaleMiddle[array_rand($this->femaleMiddle)]
            : $this->maleMiddle[array_rand($this->maleMiddle)];
        $first = $female
            ? $this->femaleNames[array_rand($this->femaleNames)]
            : $this->maleNames[array_rand($this->maleNames)];

        return trim("$last $middle $first");
    }

    private function uniqueCode(Department $dept, int $preferred): string
    {
        $base = strtoupper($dept->code);

        for ($i = $preferred; $i < $preferred + 100; $i++) {
            $code = $base.'-'.str_pad($i, 3, '0', STR_PAD_LEFT);
            if (! Employee::where('employee_code', $code)->exists()) {
                return $code;
            }
        }

        return $base.'-'.strtoupper(Str::random(4));
    }
}