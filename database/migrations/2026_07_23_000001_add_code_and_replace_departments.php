<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->after('name');
        });

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        $deptMap = [
            1 => 101,
            2 => 2,
            3 => 103,
            4 => 104,
            5 => 5,
            6 => 106,
        ];

        foreach ($deptMap as $oldId => $newId) {
            DB::table('employees')->where('department_id', $oldId)->update(['department_id' => $newId]);
        }

        DB::table('departments')->delete();

        $departments = [
            ['id' => 1,   'name' => 'Ban Giám đốc',                  'code' => 'BGD',  'description' => 'Điều hành và quản lý toàn bộ hoạt động của công ty.',                            'manager' => 'Phạm Thị Dung',      'employee_count' => 0],
            ['id' => 2,   'name' => 'Phòng Hành chính - Nhân sự',    'code' => 'HCNS', 'description' => 'Quản lý nhân sự, tuyển dụng, hợp đồng, chấm công, đào tạo và phúc lợi.',      'manager' => 'Trần Thị Bích',      'employee_count' => 0],
            ['id' => 3,   'name' => 'Phòng Kế toán - Tài chính',     'code' => 'KTTC', 'description' => 'Quản lý tài chính, kế toán, thu chi, thanh toán lương, báo cáo tài chính.',   'manager' => '',                    'employee_count' => 0],
            ['id' => 4,   'name' => 'Phòng Kinh doanh',               'code' => 'KD',   'description' => 'Tìm kiếm khách hàng, tư vấn, ký kết hợp đồng và phát triển doanh thu.',         'manager' => 'Lê Văn Cường',       'employee_count' => 0],
            ['id' => 5,   'name' => 'Phòng Marketing',                'code' => 'MKT',  'description' => 'Xây dựng thương hiệu, quảng bá sản phẩm, triển khai các chiến dịch marketing.',  'manager' => 'Hoàng Văn Nam',      'employee_count' => 0],
            ['id' => 6,   'name' => 'Phòng Công nghệ thông tin',      'code' => 'CNTT', 'description' => 'Phát triển và bảo trì hệ thống phần mềm, hạ tầng mạng, hỗ trợ kỹ thuật.',        'manager' => 'Nguyễn Văn An',      'employee_count' => 0],
            ['id' => 7,   'name' => 'Phòng Chăm sóc khách hàng',      'code' => 'CSKH', 'description' => 'Tiếp nhận phản hồi, hỗ trợ khách hàng và giải quyết khiếu nại.',                  'manager' => '',                    'employee_count' => 0],
            ['id' => 8,   'name' => 'Phòng Mua hàng',                 'code' => 'MH',   'description' => 'Tìm kiếm nhà cung cấp, mua sắm vật tư, quản lý đơn hàng.',                         'manager' => '',                    'employee_count' => 0],
            ['id' => 9,   'name' => 'Phòng Kho vận',                  'code' => 'KV',   'description' => 'Quản lý kho hàng, xuất nhập tồn và vận chuyển hàng hóa.',                           'manager' => '',                    'employee_count' => 0],
            ['id' => 10,  'name' => 'Phòng Sản xuất',                 'code' => 'SX',   'description' => 'Quản lý quy trình sản xuất và đảm bảo tiến độ sản xuất.',                           'manager' => '',                    'employee_count' => 0],
            ['id' => 11,  'name' => 'Phòng Kiểm soát chất lượng',    'code' => 'QC',   'description' => 'Kiểm tra chất lượng sản phẩm, quy trình và tiêu chuẩn sản xuất.',                   'manager' => '',                    'employee_count' => 0],
            ['id' => 12,  'name' => 'Phòng Nghiên cứu & Phát triển', 'code' => 'R&D',  'description' => 'Nghiên cứu, phát triển sản phẩm và cải tiến công nghệ.',                          'manager' => '',                    'employee_count' => 0],
            ['id' => 13,  'name' => 'Phòng Pháp chế',                 'code' => 'PC',   'description' => 'Tư vấn pháp lý, soạn thảo hợp đồng và kiểm soát rủi ro pháp lý.',                   'manager' => '',                    'employee_count' => 0],
            ['id' => 14,  'name' => 'Phòng Dự án',                    'code' => 'DA',   'description' => 'Quản lý và triển khai các dự án của công ty.',                                      'manager' => '',                    'employee_count' => 0],
            ['id' => 15,  'name' => 'Phòng Đào tạo',                  'code' => 'DT',   'description' => 'Xây dựng kế hoạch đào tạo, nâng cao năng lực nhân viên.',                          'manager' => '',                    'employee_count' => 0],
        ];

        DB::table('departments')->insert($departments);

        $finalMap = [
            101 => 6,
            2   => 2,
            103 => 4,
            104 => 1,
            5   => 5,
            106 => 3,
        ];

        foreach ($finalMap as $tempId => $newId) {
            DB::table('employees')->where('department_id', $tempId)->update(['department_id' => $newId]);
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
