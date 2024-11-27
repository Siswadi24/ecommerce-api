<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f6f9fc;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background-color: #4caf50;
            color: #ffffff;
            text-align: center;
            padding: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .body {
            padding: 20px;
            color: #333333;
            line-height: 1.6;
        }

        .otp-code {
            display: inline-block;
            font-size: 24px;
            font-weight: bold;
            background-color: #f4f4f4;
            padding: 10px 20px;
            border-radius: 4px;
            margin: 10px 0;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #777777;
            padding: 10px 20px;
            background-color: #f1f1f1;
        }

        .footer a {
            color: #4caf50;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="header">
            <h1>Verifikasi Akun Anda</h1>
        </div>
        <div class="body">
            <p>Hi, <b>{{ $user->name }}</b>,</p>
            <p>Terima kasih telah bergabung dengan kami! Berikut adalah kode OTP Anda untuk memverifikasi akun:</p>
            <p class="otp-code">{{ $user->otp_register }}</p>
            <p>Kode ini berlaku selama 5 menit. Jika Anda tidak meminta kode ini, abaikan email ini.</p>
        </div>
        <div class="footer">
            <p>Jika Anda memerlukan bantuan, kunjungi <a href="https://yourwebsite.com/help">Pusat Bantuan</a>.</p>
            <p>&copy; {{ date('Y') }} Your Company. Semua Hak Dilindungi.</p>
        </div>
    </div>
</body>

</html>
