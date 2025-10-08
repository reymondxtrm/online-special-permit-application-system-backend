<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <!-- <title>{{ $permitType }} Approved</title> -->
    <style>
        body {
            font-family: "Inter", Arial, sans-serif;
            background-color: #f4f6f8;
            padding: 40px;
            color: #333;
        }

        .card {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border-top: 6px solid #1a56db;
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
            background-color: #1a56db;
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

<body>
    <div class="card">
        <!-- <div class="header">
            <h2>Registration Verified 🎉</h2>
        </div> -->
        <div class="content">
            <p>Hi&nbsp;,{{ $user->fname }}</p>
            <p>Thank you for using Online Special Permit Application System (OSPAS). Your application for <strong>{{$permit_type}}</strong> has beedn <strong>DISAPPROVED</strong> by the office due to the following reason:</p>
            <p>{{$reason}}</p>
            <p> To proceed, please submit the required documents indicated above.</p>
            <!-- <a href="{{ $actionURL }}" class="button">Proceed to OSPAS</a> -->
            <p>For futher inquiry, please contact the Business Licensing Section at 09513884193 or email us at cbpld@butuan.gov.ph</p>
        </div>
        <div class="footer">
            <p>Thank you for using the Online Special Permit Application System (OSPAS).</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>