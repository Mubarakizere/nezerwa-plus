<!DOCTYPE html>
<html>
<head>
    <title>Account Credentials</title>
    <style>
        body { font-family: sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px; }
        .header { background: #f8f9fa; padding: 10px; text-align: center; border-bottom: 1px solid #eee; }
        .content { padding: 20px 0; }
        .credentials { background: #f1f5f9; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .footer { font-size: 12px; color: #777; text-align: center; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Welcome to KingWine</h2>
        </div>
        <div class="content">
            <p>Hello {{ $user->name }},</p>
            <p>Your account has been created successfully. Below are your login credentials:</p>
            
            <div class="credentials">
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Password:</strong> {{ $password }}</p>
            </div>

            <p>Please login and change your password immediately for security purposes.</p>
            
            <p>
                <a href="{{ route('login') }}" style="background: #4f46e5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Login Now</a>
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} KingWine. All rights reserved.
        </div>
    </div>
</body>
</html>
