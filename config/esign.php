<?php

return [
    /*
    | Nhà cung cấp chữ ký số.
    | mock = mô phỏng quy trình (DATN). Khi triển khai thật, đổi sang driver API.
    */
    'provider' => env('ESIGN_PROVIDER', 'mock'),

    'mock_secret' => env('ESIGN_MOCK_SECRET', env('APP_KEY')),

    /*
    | Nhãn hiển thị khi demo. Không tuyên bố giá trị pháp lý.
    */
    'disclaimer' => 'Mô phỏng quy trình ký số để minh họa kiến trúc tích hợp. Triển khai thực tế sẽ kết nối nhà cung cấp chứng thư số qua API.',
];
