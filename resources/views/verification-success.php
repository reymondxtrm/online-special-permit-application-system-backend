<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verified</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #0752f8;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background-color: #ffffff;
            padding: 60px;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            text-align: center;
            max-width: 500px;
        }

        .check-circle {
            width: 150px;
            /* Increased size */
            height: 150px;
            /* Increased size */
            margin: 0 auto 30px;
        }

        h1 {
            color: #111312ff;
            font-size: 32px;
            margin-bottom: 20px;
        }

        p {
            color: #4b5563;
            font-size: 18px;
            margin-bottom: 30px;
        }

        .button {
            display: inline-block;
            background-color: #22c55e;
            color: #ffffff;
            padding: 16px 32px;
            font-size: 18px;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.3s;
        }

        .button:hover {
            background-color: #16a34a;
        }
    </style>
    <script>
        const frontendApp = "{{ config('app.frontend_app') }}/home";
        const frontendApp1 = "http://localhost:3000/home";
    </script>
</head>

<body>
    <div class="container">

        <svg class="check-circle" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="48" fill="#22c55e" />
            <path d="M30 52l15 15 25-35" fill="none" stroke="#ffffff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" />
        </svg>

        <h1>Email Verified Successfully!</h1>
        <p>Thank you for verifying your email. You can now access all features of your account.</p>
        <button class="button" onclick="window.location.href=frontendApp1">
            Go to Homepage
        </button>
    </div>
</body>

</html>