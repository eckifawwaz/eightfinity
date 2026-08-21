<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Eightfinity Admin Two-Factor Code</title>
</head>
<body style="font-family: Arial, sans-serif; color: #172033; line-height: 1.5;">
    <h1 style="color: #202660;">Admin sign-in verification</h1>
    <p>Hi {{ $user->name }},</p>
    <p>Use this code to finish signing in to your Eightfinity admin account:</p>
    <p style="font-size: 32px; font-weight: 700; letter-spacing: 6px; color: #f4aa3f;">{{ $code }}</p>
    <p>This code expires in 10 minutes.</p>
    <p>
        Verification page:
        <a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a>
    </p>
    <p>If you did not try to sign in, change your password immediately.</p>
</body>
</html>
