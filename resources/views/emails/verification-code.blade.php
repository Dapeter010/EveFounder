<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email for EveFound</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            background: linear-gradient(135deg, #ec4899 0%, #9333ea 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        h2 {
            color: #333;
            font-size: 24px;
            margin-top: 10px;
        }
        p {
            color: #666;
            line-height: 1.6;
            font-size: 16px;
        }
        .code {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 8px;
            text-align: center;
            color: #ec4899;
            padding: 20px;
            border: 2px dashed #ec4899;
            border-radius: 8px;
            margin: 20px 0;
            background-color: #fef2f8;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .footer p {
            margin: 5px 0;
        }
        strong {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">EveFound</div>
            <h2>Verify Your Email Address</h2>
        </div>

        <p>Welcome to EveFound! 🎉</p>

        <p>To complete your registration, please enter the verification code below on the signup page:</p>

        <div class="code">{{ $code }}</div>

        <p><strong>This code will expire in 5 minutes.</strong></p>

        <p>If you didn't request this code, please ignore this email.</p>

        <div class="footer">
            <p>&copy; 2025 EveFound. All rights reserved.</p>
            <p>This is an automated email, please do not reply.</p>
        </div>
    </div>
</body>
</html>
