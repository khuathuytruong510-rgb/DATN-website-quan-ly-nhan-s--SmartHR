<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Database\Seeders\PositionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('positions')) {
            return;
        }

        if (! Schema::hasColumn('positions', 'department_id')) {
            Schema::table('positions', function (Blueprint $table) {
                $table->unsignedBigInteger('department_id')->nullable()->after('name');
                $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            });
        }

        if (Department::count() > 0) {
            $this->restructureDepartments();
            $this->restructurePositions();
            app(PositionSeeder::class)->run();
            $this->cleanupPositions();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('positions') && Schema::hasColumn('positions', 'department_id')) {
            Schema::table('positions', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            });
        }
    }

    private function restructureDepartments(): void
    {
        $this->mergeDepartments(
            ['HCNS', 'HR'],
            'HR',
            'Phòng Nhân sự (HR)',
            'Trần Thị Bích',
            'Quản lý nhân sự, hồ sơ, chấm công, nghỉ phép và chính sách.'
        );
        $this->mergeDepartments(
            ['CNTT', 'IT'],
            'IT',
            'Phòng IT',
            'Nguyễn Văn An',
            'Phát triển phần mềm, vận hành hệ thống và hỗ trợ kỹ thuật.'
        );
        $this->mergeDepartments(
            ['KTTC', 'FINA'],
            'KTTC',
            'Phòng Kế toán - Tài chính',
            'Lê Thị Mai',
            'Tính lương, kế toán, thu chi và thanh toán.'
        );

        $sales = Department::where('code', 'SALE')->first();
        if ($sales) {
            $sales->update([
                'code' => 'KD',
                'name' => 'Phòng Kinh doanh',
                'manager' => 'Lê Văn Cường',
                'description' => 'Tìm kiếm khách hàng, tư vấn, ký kết hợp đồng và phát triển doanh thu.',
            ]);
        }

        $bgd = Department::where('code', 'BGD')->first();
        if ($bgd) {
            $bgd->update([
                'name' => 'Ban Giám đốc',
                'description' => 'Điều hành và phê duyệt cấp cao.',
            ]);
        }

        $new = [
            ['code' => 'TD',   'name' => 'Phòng Tuyển dụng',           'manager' => '', 'description' => 'Tuyển dụng, sàng lọc và onboarding nhân sự mới.'],
            ['code' => 'CB',   'name' => 'Phòng C&B',                  'manager' => '', 'description' => 'Lương thưởng, chế độ phúc lợi và đãi ngộ cho nhân viên.'],
            ['code' => 'DTPT', 'name' => 'Phòng Đào tạo & Phát triển', 'manager' => '', 'description' => 'Đào tạo kỹ năng và phát triển năng lực nhân viên.'],
            ['code' => 'MKT',  'name' => 'Phòng Marketing',            'manager' => 'Hoàng Văn Nam', 'description' => 'Xây dựng thương hiệu, quảng bá sản phẩm và triển khai chiến dịch.'],
            ['code' => 'VH',   'name' => 'Phòng Vận hành',             'manager' => '', 'description' => 'Đảm bảo quy trình vận hành và logistics của công ty.'],
            ['code' => 'PC',   'name' => 'Phòng Pháp chế',             'manager' => '', 'description' => 'Tư vấn pháp lý, soạn thảo hợp đồng và kiểm soát rủi ro.'],
            ['code' => 'HC',   'name' => 'Phòng Hành chính',           'manager' => '', 'description' => 'Quản lý hành chính, văn phòng và hậu cần nội bộ.'],
        ];
        foreach ($new as $d) {
            Department::updateOrCreate(['code' => $d['code']], $d);
        }
    }

    private function mergeDepartments(array $sourceCodes, string $code, string $name, string $manager, string $description): void
    {
        $canonical = null;
        foreach ($sourceCodes as $source) {
            $canonical = Department::where('code', $source)->first();
            if ($canonical) {
                break;
            }
        }

        if (! $canonical) {
            Department::updateOrCreate(['code' => $code], [
                'name' => $name,
                'manager' => $manager,
                'description' => $description,
            ]);

            return;
        }

        foreach ($sourceCodes as $source) {
            if ($source === $canonical->code) {
                continue;
            }
            $other = Department::where('code', $source)->first();
            if (! $other) {
                continue;
            }
            Employee::where('department_id', $other->id)->update(['department_id' => $canonical->id]);
            $other->delete();
        }

        $canonical->update([
            'code' => $code,
            'name' => $name,
            'manager' => $manager,
            'description' => $description,
        ]);
    }

    private function restructurePositions(): void
    {
        $deptId = fn (string $code) => optional(Department::where('code', $code)->first())->id;

        $renames = [
            'Giám đốc'                 => ['Giám đốc', 'BGD'],
            'Tổng Giám đốc'            => ['Giám đốc', 'BGD'],
            'Phó Giám đốc'             => ['Giám đốc', 'BGD'],
            'Phó Tổng Giám đốc'        => ['Giám đốc', 'BGD'],
            'Trưởng phòng Nhân sự'     => ['Trưởng phòng HR', 'HR'],
            'Trưởng phòng Kế toán'     => ['Kế toán trưởng', 'KTTC'],
            'Trưởng phòng IT'          => ['IT Manager', 'IT'],
            'Trưởng phòng Kinh doanh'  => ['Sales Manager', 'KD'],
            'Nhân viên Kinh doanh'     => ['Sales Representative', 'KD'],
            'Nhân viên Văn phòng'      => ['Administrative Officer', 'HC'],
        ];
        foreach ($renames as $old => [$new, $code]) {
            $position = Position::where('name', $old)->first();
            if ($position && $deptId($code)) {
                $position->update(['name' => $new, 'department_id' => $deptId($code)]);
            }
        }

        $kept = [
            'HR Manager'      => 'HR',
            'Sales Executive' => 'KD',
        ];
        foreach ($kept as $name => $code) {
            $position = Position::where('name', $name)->first();
            if ($position && $deptId($code)) {
                $position->update(['department_id' => $deptId($code)]);
            }
        }
    }

    private function cleanupPositions(): void
    {
        $deleteRepoint = [
            'CTO'                  => 'IT Manager',
            'Senior Developer'     => 'Developer',
            'Finance Officer'      => 'Finance Manager',
            'Marketing Lead'       => null,
            'Chuyên viên Nhân sự'  => 'HR Executive',
            'Kế toán viên'         => 'Accountant',
            'Backend Developer'    => 'Developer',
            'Frontend Developer'   => 'Developer',
            'Thực tập sinh'        => null,
        ];
        foreach ($deleteRepoint as $old => $target) {
            $position = Position::where('name', $old)->first();
            if (! $position) {
                continue;
            }
            if ($target) {
                $targetPosition = Position::where('name', $target)->first();
                if ($targetPosition) {
                    Employee::where('position_id', $position->id)->update(['position_id' => $targetPosition->id]);
                }
            }
            $position->delete();
        }
    }
};