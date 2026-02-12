<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <script>
        const deepLink = "inventaris://reset-password?token={{ $token }}&email={{ $email }}";

        // coba buka app
        window.location.href = deepLink;

        // fallback ke web setelah 1.5 detik
        setTimeout(() => {
            document.getElementById("web-form").style.display = "block";
        }, 1500);
    </script>
</head>
<body>
    <h2>Redirecting to app...</h2>

    <div id="web-form" style="display:none;">
        <p>App not installed? Reset via web:</p>

        <form method="POST" action="/api/reset-password">
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <input type="password" name="password" placeholder="New password">
            <input type="password" name="password_confirmation" placeholder="Confirm">

            <button type="submit">Reset</button>
        </form>
    </div>
</body>
</html>
