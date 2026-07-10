document.addEventListener('DOMContentLoaded', () => {

    console.log('Payroll JS loaded');


    const csrf = document.querySelector(
        'meta[name="csrf-token"]'
    )?.getAttribute('content');


    // Gửi bảng lương
    document.querySelectorAll('.btn-send-payroll')
        .forEach(button => {

            button.addEventListener('click', async () => {

                const id = button.dataset.id;


                if(!confirm('Gửi bảng lương cho nhân viên?')){
                    return;
                }


                try {

                    const response = await fetch(
                        `/api/payroll/${id}/send`,
                        {
                            method: 'POST',
                            headers:{
                                'X-CSRF-TOKEN': csrf,
                                'Accept':'application/json',
                                'Content-Type':'application/json'
                            }
                        }
                    );


                    const data = await response.json();


                    if(response.ok){

                        alert(
                            'Đã gửi bảng lương thành công'
                        );

                    }else{

                        alert(
                            data.message ?? 'Có lỗi xảy ra'
                        );

                    }


                } catch(error){

                    console.error(error);
                    alert('Lỗi kết nối server');

                }

            });

        });



    // Thanh toán
    document.querySelectorAll('.pay-btn')
        .forEach(button => {

            button.addEventListener('click', async () => {

                const id = button.dataset.id;


                if(!confirm('Xác nhận thanh toán?')){
                    return;
                }


                try {

                    const response = await fetch(
                        `/api/payroll/${id}/pay`,
                        {
                            method:'POST',
                            headers:{
                                'X-CSRF-TOKEN': csrf,
                                'Accept':'application/json',
                                'Content-Type':'application/json'
                            }
                        }
                    );


                    const data = await response.json();


                    if(response.ok){

                        alert(
                            'Thanh toán thành công'
                        );

                        location.reload();

                    }else{

                        alert(
                            data.message ?? 'Có lỗi xảy ra'
                        );

                    }


                } catch(error){

                    console.error(error);
                    alert('Lỗi kết nối server');

                }


            });

        });


});// placeholder for resources/js/payroll.js\n
