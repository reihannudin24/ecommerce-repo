<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Request</title>
</head>
<body>
<h1>Password Reset Request</h1>
<p>You have requested to reset your password. Click the link below to reset your password:</p>
<a href="{{ url('/reset-password/' . $resetToken) }}">Reset Password</a>
<p>If you did not request this, please ignore this email.</p>
</body>
</html>
