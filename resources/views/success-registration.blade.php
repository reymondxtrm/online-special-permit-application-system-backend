<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: "Inter", Arial, sans-serif;
            background-color: #f4f6f88f;
            padding: 40px;
            color: #333;
        }

        .card {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            /* border-radius: 10px; */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border-top: 6px solid #0e6672ff;
            /* Blue top border */
        }

        .header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
        }

        .header h2 {
            margin: 0;
            font-size: 20px;
            color: #1a56db;
        }

        .content {
            padding: 25px;
        }

        .content p {
            line-height: 1.6;
            font-size: 15px;
        }

        .button {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background-color: #0e6672ff;
            color: #fff;
            font-weight: 600;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.2s;
        }

        .button:hover {
            background-color: #174bb3;
        }

        .footer {
            padding: 15px 25px;
            text-align: center;
            font-size: 13px;
            color: #777;
            border-top: 1px solid #eee;
        }
    </style>
</head>

<body style="background-color:#f4f6f88f">
    <div style="text-align:center; margin-bottom: 20px;">
        @php
        $logoPath = public_path('images/emailLogo.png');
        if (isset($message) && file_exists($logoPath)) {
        $logoSrc = $message->embed($logoPath);
        } else {
        $base = isset($actionUrl) && $actionUrl ? rtrim($actionUrl, '/') : rtrim(config('app.url', ''), '/');
        if ($base) {
        $logoSrc = $base . '/images/emailLogo.png';
        } else {
        $logoSrc = asset('images/emailLogo.png');
        }
        }
        @endphp
        <img src="{{ $logoSrc }}" alt="Logo" width="350" style="display:inline-block;" />
    </div>
    <div class="card">
        <div class="content">
            <p>Congratulation&nbsp;,{{ $user->fname }}</p>
            <p>You have successfully registered your account. You may now apply for Mayors Clearance, Mayor's Certification and Special Permits.
            </p>
            <div style="text-align:center;">
                <a href="{{ $actionUrl }}" class="button" style="color: #fff; display: inline-block;">Visit OSPAS</a>
            </div>
            <p>For futher inquiry, please contact the Business Licensing Section at 09513884193 or email us at cbpld@butuan.gov.ph</p>
        </div>

    </div>
    <div class="footer">
        <p>Thank you for using the Online Special Permit Application System (OSPAS).</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>

</html>