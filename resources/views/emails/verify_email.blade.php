<!DOCTYPE html>
<html>
<head>
    <title>Email Verification</title>
</head>
<body>
<h1>Email Verification</h1>
<p>Thank you for registering! Please click the link below to verify your email address:</p>
<a href="{{ url('/verify-email/' . $verificationToken) }}">Verify Email</a>
<p>If you did not register, please ignore this email.</p>
</body>
</html>
