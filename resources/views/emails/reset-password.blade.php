<div style="background-color: #e1e8ed">
    <div
        style="background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); overflow: hidden; width:
        600px; margin: 50px auto; font-family: 'Helvetica Neue' , Helvetica, Arial, sans-serif; color: #333;">
        <div style="padding: 15px; font-weight: bold; border-bottom: 1px solid #ddd; font-size: 18px;">
            <img src='https://mykrr.co.id/images/KRR.png' alt="KRR Logo"
                style=" width: 40px; height: 40px;
                object-fit: contain;">
            Hi, {{ $user->NamaUser }}!
        </div>
        <div style="padding: 20px">
            <p>Kami menerima permintaan untuk mengatur ulang password akun Anda.</p>

            <p>
                Silakan klik tombol berikut untuk membuat password baru:
            </p>

            <p>
                <a href='{{ $link }}'
                    style='display:inline-block;
                              padding:10px 20px;
                              background-color:#0d6efd;
                              color:#ffffff;
                              text-decoration:none;
                              border-radius:5px;'>
                    Reset Password
                </a>
            </p>

            <p>
                Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini dan tidak ada perubahan yang akan
                dilakukan
                pada akun Anda.
            </p>

            <p>Terima kasih.</p>
        </div>
        <div style="border-top: 1px solid #ddd;">
            <div style="padding: 10px;">
                <p style="font-size:12px;color:#666;line-height:1.5;">
                    <strong>Disclaimer:</strong> Email ini dikirim secara otomatis oleh sistem.
                    Mohon tidak membalas email ini.
                </p>
            </div>
        </div>
    </div>
</div>
