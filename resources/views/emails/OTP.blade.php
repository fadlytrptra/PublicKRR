<div style="background-color: #e1e8ed;">
    <div
        style="
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 600px;
            margin: 50px auto;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
        ">

        <div
            style="
                padding: 15px;
                font-weight: bold;
                border-bottom: 1px solid #ddd;
                font-size: 18px;
            "
        >
            <img
                src="https://mykrr.co.id/images/KRR.png"
                alt="KRR Logo"
                style="
                    width: 40px;
                    height: 40px;
                    object-fit: contain;
                    vertical-align: middle;
                    margin-right: 10px;
                "
            >

            Hi, {{ $user }}!
        </div>

        <div style="padding: 20px;">
            <p>
                Kami menerima permintaan untuk melakukan {{ $activity }}.
            </p>

            <p>
                Gunakan kode OTP berikut untuk melanjutkan proses verifikasi:
            </p>

            <div style="text-align: center; margin: 30px 0;">
                <div
                    style="
                        display: inline-block;
                        padding: 15px 30px;
                        background-color: #f5f5f5;
                        border: 1px dashed #0d6efd;
                        border-radius: 8px;
                        font-size: 32px;
                        font-weight: bold;
                        letter-spacing: 8px;
                        color: #0d6efd;
                    "
                >
                    {{ $otp }}
                </div>
            </div>

            <p>
                Kode OTP ini hanya berlaku selama
                <strong>5 menit</strong>.
            </p>

            <p>
                Demi keamanan, jangan membagikan kode OTP ini kepada siapa pun,
                termasuk pihak yang mengaku sebagai administrator atau tim support.
            </p>

            <p>
                Jika Anda tidak merasa melakukan permintaan ini,
                abaikan email ini dan tidak ada perubahan yang akan dilakukan
                pada akun Anda.
            </p>

            <p>Terima kasih.</p>
        </div>

        <div style="border-top: 1px solid #ddd;">
            <div style="padding: 10px;">
                <p style="font-size: 12px; color: #666; line-height: 1.5;">
                    <strong>Disclaimer:</strong>
                    Email ini dikirim secara otomatis oleh sistem.
                    Mohon tidak membalas email ini.
                </p>
            </div>
        </div>

    </div>
</div>
