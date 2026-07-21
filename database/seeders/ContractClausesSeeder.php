<?php

namespace Database\Seeders;

use App\Models\ContractClause;
use Illuminate\Database\Seeder;

class ContractClausesSeeder extends Seeder
{
    public function run(): void
    {
        // Standard clauses applicable to all contract types
        $commonClauses = [
            ['section_number' => '1', 'section_title' => 'THỎA THUẬN CHUNG', 'contract_types' => ['internship', 'probation', 'official', 'seasonal'], 'content' => 'Công ty và nhân viên thỏa thuận ký kết hợp đồng này với mục đích: Công ty cần tuyển dụng nhân viên; Nhân viên tự nguyện làm việc cho Công ty, tuân thủ các quy định về lao động của pháp luật Việt Nam.'],
            ['section_number' => '2', 'section_title' => 'THÔNG TIN CÁ NHÂN', 'contract_types' => ['internship', 'probation', 'official', 'seasonal'], 'content' => 'Nhân viên cam kết cung cấp các thông tin cá nhân chính xác, đầy đủ và không bị sai lệch. Công ty có quyền yêu cầu cung cấp các giấy tờ chứng thực nếu cần thiết.'],
            ['section_number' => '3', 'section_title' => 'VỊ TRÍ CÔNG VIỆC VÀ TRÁCH NHIỆM', 'contract_types' => ['internship', 'probation', 'official', 'seasonal'], 'content' => 'Nhân viên chịu trách nhiệm thực hiện công việc được giao theo các tiêu chuẩn chất lượng của Công ty. Công ty có quyền sắp xếp, chuyển đổi vị trí làm việc phù hợp với nhu cầu kinh doanh và năng lực của nhân viên.'],
            ['section_number' => '4', 'section_title' => 'GIỜ LÀM VIỆC', 'contract_types' => ['internship', 'probation', 'official', 'seasonal'], 'content' => 'Thời gian làm việc bình thường là 40 giờ/tuần (8 giờ/ngày), từ thứ 2 đến thứ 6, từ 8h00 sáng đến 17h00 chiều (có ngừng giữa trưa 1 giờ). Công ty có quyền yêu cầu nhân viên làm thêm giờ khi cần thiết, với mức trả công theo quy định của pháp luật.'],
            ['section_number' => '5', 'section_title' => 'CHẾ ĐỘ LƯƠNG VÀ PHÚC LỢI', 'contract_types' => ['internship', 'probation', 'official', 'seasonal'], 'content' => 'Lương tháng và các phúc lợi được quy định trong phụ lục kèm theo hợp đồng này. Lương được trả hàng tháng vào ngày 25 hàng tháng. Công ty giải quyết các tranh chấp về lương trong vòng 15 ngày kể từ khi nhận được khiếu nại từ nhân viên.'],
            ['section_number' => '6', 'section_title' => 'AN TOÀN LÀM VIỆC VÀ SỨC KHỎE', 'contract_types' => ['internship', 'probation', 'official', 'seasonal'], 'content' => 'Công ty cung cấp môi trường làm việc an toàn, bảo vệ sức khỏe của nhân viên theo quy định pháp luật. Nhân viên phải tuân thủ các quy định về an toàn lao động, sử dụng đúng các thiết bị bảo vệ cá nhân được cung cấp.'],
            ['section_number' => '7', 'section_title' => 'BẢO MẬT VÀ QUYỀN SỬ DỤNG CÔNG NGHỆ', 'contract_types' => ['internship', 'probation', 'official', 'seasonal'], 'content' => 'Nhân viên cam kết bảo mật mọi thông tin bí mật, tài liệu, tài sản của Công ty. Thiết bị công nghệ được cung cấp chỉ dùng cho công việc phục vụ Công ty. Khi kết thúc hợp đồng, nhân viên phải trả lại toàn bộ thiết bị và tài liệu.'],
            ['section_number' => '8', 'section_title' => 'KỈ律LUẬT LỤC VÀ HÀNH VI CÓ THỂ LÝ DO CHẤM DỨT HỢP ĐỒNG', 'contract_types' => ['internship', 'probation', 'official', 'seasonal'], 'content' => 'Nhân viên phải tuân thủ nội quy lao động của Công ty. Các hành vi vi phạm kỉ luật lao động, lừa dối, trộm cắp, sử dụng chất cấm, hoặc gây thiệt hại lớn cho Công ty có thể là lý do để Công ty chấm dứt hợp đồng lao động.'],
            ['section_number' => '9', 'section_title' => 'TRANH CHẤP VÀ GIẢI QUYẾT', 'contract_types' => ['internship', 'probation', 'official', 'seasonal'], 'content' => 'Các tranh chấp phát sinh từ hợp đồng này sẽ được giải quyết thông qua thương lượng. Nếu không đi đến thỏa thuận, các tranh chấp sẽ được đưa ra Tòa án hoặc Cơ quan Trọng tài có thẩm quyền.'],
        ];

        foreach ($commonClauses as $i => $clause) {
            $contractTypes = $clause['contract_types'];
            unset($clause['contract_types']);
            
            foreach ($contractTypes as $type) {
                ContractClause::create(array_merge($clause, [
                    'contract_type' => $type,
                    'order' => $i + 1,
                    'is_mandatory' => true,
                    'status' => 'active',
                ]));
            }
        }

        // Type-specific clauses
        $typeSpecificClauses = [
            'internship' => [
                ['section_number' => '10', 'section_title' => 'THỜI HẠN THỰC TẬP', 'content' => 'Kỳ thực tập kéo dài từ ngày ___/___/_____ đến ngày ___/___/_____. Sau kỳ thực tập, Công ty sẽ đánh giá năng lực của thực tập sinh để quyết định có tuyển dụng thành nhân viên chính thức hay không.', 'order' => 10],
                ['section_number' => '11', 'section_title' => 'TIỀN THỰC TẬP', 'content' => 'Tiền thực tập hàng tháng là ________ VND. Ngoài ra, thực tập sinh không được hưởng các phúc lợi khác như bảo hiểm xã hội, bảo hiểm y tế.', 'order' => 11],
                ['section_number' => '12', 'section_title' => 'NGHĨA VỤ CỦA CÔNG TY ĐỐI VỚI THỰC TẬP SINH', 'content' => 'Công ty cam kết: (1) Hướng dẫn chi tiết công việc cho thực tập sinh; (2) Cung cấp các tài liệu học tập cần thiết; (3) Đánh giá định kỳ hiệu suất làm việc; (4) Cấp giấy chứng nhận thực tập khi kết thúc kỳ hạn.', 'order' => 12],
            ],
            'probation' => [
                ['section_number' => '10', 'section_title' => 'THỜI GIẪ THỬ VIỆC', 'content' => 'Thời gian thử việc là 3 tháng kể từ ngày nhân viên bắt đầu làm việc. Trong thời gian thử việc, cả hai bên có quyền chấm dứt hợp đồng với thông báo trước 03 ngày làm việc mà không cần bồi thường.', 'order' => 10],
                ['section_number' => '11', 'section_title' => 'LƯƠNG THỜI GIẦ THỬ VIỆC', 'content' => 'Mức lương trong thời gian thử việc là ________ VND/tháng, tương đương ___% mức lương chính thức. Sau khi vượt qua thử việc thành công, mức lương sẽ được điều chỉnh theo quyết định của Công ty.', 'order' => 11],
                ['section_number' => '12', 'section_title' => 'ĐÁNH GIÁ THÀNH QUẢ THỬ VIỆC', 'content' => 'Ngành kinh doanh sẽ thực hiện đánh giá toàn diện kỹ năng, năng suất và phù hợp với môi trường làm việc của nhân viên. Kết quả đánh giá sẽ quyết định tiếp tục hay chấm dứt hợp đồng.', 'order' => 12],
            ],
            'official' => [
                ['section_number' => '10', 'section_title' => 'THỜI HẠN HỢP ĐỒNG', 'content' => 'Hợp đồng lao động này có hiệu lực từ ngày ___/___/_____ và không xác định thời hạn, trừ khi được sửa đổi bằng phụ lục hoặc thỏa thuận bổ sung. Hợp đồng này thay thế bất kỳ thỏa thuận bằng miệng hoặc viết trước đó giữa hai bên.', 'order' => 10],
                ['section_number' => '11', 'section_title' => 'BẢO HIỂM XÃ HỘI, Y TẾ VÀ THẤT NGHIỆP', 'content' => 'Công ty đăng ký và nộp đầy đủ các khoản bảo hiểm xã hội, bảo hiểm y tế và bảo hiểm thất nghiệp cho nhân viên theo quy định pháp luật Việt Nam. Mức đóng bảo hiểm được tính trên mức lương cơ bản.', 'order' => 11],
                ['section_number' => '12', 'section_title' => 'NGHỈ PHÉP HÀNG NĂM', 'content' => 'Nhân viên được hưởng 12 ngày nghỉ phép có lương hàng năm (tính từ tháng 1 đến tháng 12). Ngày nghỉ chưa sử dụng có thể được để lại sang năm tiếp theo nhưng không quá 6 ngày. Việc lấy nghỉ phép phải được phê duyệt trước bởi Quản lý trực tiếp.', 'order' => 12],
                ['section_number' => '13', 'section_title' => 'CHẾ ĐỘ THĂM DÒNG VÀ HƯỞNG PHÚC LỢI KHÁC', 'content' => 'Nhân viên được hưởng các ngày lễ, Tết theo quy định pháp luật. Thêm vào đó, Công ty cung cấp các phúc lợi khác bao gồm: cấp cứu y tế, hỗ trợ học tập chuyên nghiệp, các hoạt động xã hội và vui chơi giải trí do Công ty tổ chức.', 'order' => 13],
            ],
            'seasonal' => [
                ['section_number' => '10', 'section_title' => 'THỜI HẠN HỢP ĐỒNG MÙA', 'content' => 'Hợp đồng này có hiệu lực từ ngày ___/___/_____ đến ngày ___/___/_____, trong thời hạn _______ tháng. Hợp đồng sẽ tự động chấm dứt khi hết thời hạn, trừ khi được gia hạn bằng thỏa thuận bằng văn bản.', 'order' => 10],
                ['section_number' => '11', 'section_title' => 'LƯƠNG VÀ THANH TOÁN', 'content' => 'Mức lương hàng tháng là ________ VND. Lương được trả theo ngày làm việc thực tế. Khi kết thúc hợp đồng, lương cùng với các khoản phụ cấp (nếu có) phải được thanh toán đầy đủ trong vòng 03 ngày làm việc.', 'order' => 11],
                ['section_number' => '12', 'section_title' => 'QUY ĐỊNH RIÊNG VỀ CÔNG VIỆC MÙA VỤ', 'content' => 'Nhân viên mùa vụ tuân thủ hoàn toàn lịch trình làm việc được Công ty xác định. Công ty có quyền thay đổi lịch làm việc mà không cần thông báo trước để phù hợp với nhu cầu kinh doanh theo từng mùa vụ.', 'order' => 12],
                ['section_number' => '13', 'section_title' => 'KHAI THÚC HỢP ĐỒNG', 'content' => 'Khi hết hạn hợp đồng, nhân viên phải trả lại đầy đủ tài liệu, thiết bị và bất kỳ tài sản của Công ty. Công ty sẽ giải quyết mọi khoản công nợ với nhân viên trước khi chấm dứt hợp đồng.', 'order' => 13],
            ],
        ];

        $baseOrder = 10;
        foreach ($typeSpecificClauses as $type => $clauses) {
            foreach ($clauses as $i => $clause) {
                ContractClause::create(array_merge($clause, [
                    'contract_type' => $type,
                    'is_mandatory' => true,
                    'status' => 'active',
                ]));
            }
        }
    }
}
