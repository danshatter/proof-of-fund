<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Agent Verification</title>
</head>
<body>
    <p>Thank you for registering with us. Click on the link or copy and paste the URL below to verify your account</p>
    <br>
    <a href="{{ route('auth.verify-email', ['token' => $agent->email_verification]) }}">{{ route('auth.verify-email', ['token' => $agent->email_verification]) }}</a>
</body>
</html>