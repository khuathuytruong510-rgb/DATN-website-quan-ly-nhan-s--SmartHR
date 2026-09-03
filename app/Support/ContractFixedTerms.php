<?php

namespace App\Support;

class ContractFixedTerms
{
    public static function forType(?string $contractType): string
    {
        return self::terms()[$contractType] ?? '';
    }

    public static function terms(): array
    {
        $common = <<<'TEXT'

ĐIỀU KHOẢN CHUNG

1. Người lao động cam kết thực hiện đúng nội quy lao động.

2. Giữ bí mật thông tin, dữ liệu và tài sản của công ty.

3. Không thực hiện các hành vi gây thiệt hại đến công ty.

4. Hoàn trả tài sản công ty khi chấm dứt hợp đồng.

5. Mọi tranh chấp sẽ được giải quyết theo Bộ luật Lao động Việt Nam.

6. Hợp đồng được lập thành 02 bản có giá trị pháp lý như nhau.

7. Hai bên cam kết thực hiện đầy đủ các điều khoản đã ký.
TEXT;

        return [
            'internship' => <<<'TEXT'
ĐIỀU KHOẢN HỢP ĐỒNG THỰC TẬP

1. Thời gian thực tập theo hợp đồng.

2. Thời gian làm việc bình thường: 08 giờ/ngày, 05 ngày/tuần (Thứ Hai đến Thứ Sáu).

3. Phụ cấp thực tập:
- Theo mức phụ cấp đã thỏa thuận.
- Không phải tiền lương chính thức.

4. Người thực tập không thuộc đối tượng tham gia BHXH, BHYT, BHTN theo quy định hiện hành (trừ trường hợp pháp luật có quy định khác).

5. Được nghỉ lễ, nghỉ Tết theo quy định của Nhà nước.

6. Được đánh giá kết quả thực tập khi kết thúc thời gian thực tập.

7. Hai bên có quyền chấm dứt hợp đồng khi có thông báo trước ít nhất 03 ngày làm việc.
TEXT . $common,
            'probation' => <<<'TEXT'
ĐIỀU KHOẢN HỢP ĐỒNG THỬ VIỆC

1. Thời gian thử việc theo hợp đồng.

2. Mức lương thử việc bằng 85% mức lương chính thức.

3. Thời gian làm việc bình thường: 08 giờ/ngày, 05 ngày/tuần (Thứ Hai đến Thứ Sáu).

4. Người lao động phải tuân thủ nội quy lao động của công ty.

5. Sau thời gian thử việc:
- Nếu đạt yêu cầu sẽ ký Hợp đồng lao động chính thức.
- Nếu không đạt yêu cầu hợp đồng sẽ chấm dứt.

6. Hai bên có quyền chấm dứt hợp đồng theo quy định của Bộ luật Lao động.
TEXT . $common,
            'fixed_term' => <<<'TEXT'
ĐIỀU KHOẢN HỢP ĐỒNG LAO ĐỘNG XÁC ĐỊNH THỜI HẠN

1. Thời hạn hợp đồng theo thời gian đã thỏa thuận.

2. Mức lương và phụ cấp được trả theo chức vụ.

3. Tham gia đầy đủ:
- BHXH
- BHYT
- BHTN

4. Nghỉ phép năm theo quy định của Bộ luật Lao động.

5. Được hưởng:
- Thưởng lễ.
- Thưởng Tết.
- Thưởng hiệu quả công việc (nếu có).

6. Được xét tăng lương theo quy chế công ty.

7. Hai bên thực hiện đầy đủ quyền và nghĩa vụ theo Bộ luật Lao động hiện hành.
TEXT . $common,
            'official' => <<<'TEXT'
ĐIỀU KHOẢN HỢP ĐỒNG LAO ĐỘNG XÁC ĐỊNH THỜI HẠN

1. Thời hạn hợp đồng theo thời gian đã thỏa thuận.

2. Mức lương và phụ cấp được trả theo chức vụ.

3. Tham gia đầy đủ:
- BHXH
- BHYT
- BHTN

4. Nghỉ phép năm theo quy định của Bộ luật Lao động.

5. Được hưởng:
- Thưởng lễ.
- Thưởng Tết.
- Thưởng hiệu quả công việc (nếu có).

6. Được xét tăng lương theo quy chế công ty.

7. Hai bên thực hiện đầy đủ quyền và nghĩa vụ theo Bộ luật Lao động hiện hành.
TEXT . $common,
            'indefinite' => <<<'TEXT'
ĐIỀU KHOẢN HỢP ĐỒNG KHÔNG XÁC ĐỊNH THỜI HẠN

1. Hợp đồng có hiệu lực kể từ ngày ký và không xác định thời điểm kết thúc.

2. Người lao động được hưởng đầy đủ chế độ:
- BHXH
- BHYT
- BHTN

3. Mức lương theo chức vụ và được điều chỉnh theo chính sách của công ty.

4. Được nghỉ phép năm theo quy định.

5. Được tham gia các chương trình đào tạo.

6. Được hưởng đầy đủ các chế độ phúc lợi của công ty.

7. Chấm dứt hợp đồng theo quy định của Bộ luật Lao động.
TEXT . $common,
            'seasonal' => <<<'TEXT'
ĐIỀU KHOẢN HỢP ĐỒNG THỜI VỤ

1. Thời gian làm việc theo mùa vụ hoặc công việc cụ thể.

2. Hợp đồng tự chấm dứt khi hoàn thành công việc hoặc hết thời hạn.

3. Mức lương được thanh toán theo thời gian hoặc sản phẩm.

4. Người lao động phải tuân thủ nội quy lao động.

5. Chế độ bảo hiểm thực hiện theo quy định của pháp luật.

6. Hai bên có quyền chấm dứt hợp đồng theo quy định.
TEXT . $common,
        ];
    }
}
