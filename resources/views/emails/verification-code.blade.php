<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Eightfinity Verification Code</title>
</head>
<body style="font-family: Arial, sans-serif; color: #172033; line-height: 1.5;">
    <h1 style="color: #202660;">Verify your Eightfinity account</h1>
    <p>Hi {{ $user->name }},</p>
    <p>Use this verification code to activate your Eightfinity account:</p>
    <p style="font-size: 32px; font-weight: 700; letter-spacing: 6px; color: #f4aa3f;">{{ $code }}</p>
    <p>This code expires in 10 minutes.</p>
    <p>If you did not create an Eightfinity account, you can ignore this email.</p>
</body>
</html>
