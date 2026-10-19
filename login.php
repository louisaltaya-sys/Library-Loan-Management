<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'user.php';

if (isAuthenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(postString('username'));
    $password = postString('password');

    if (!isValidCsrfToken(postString('csrf_token'))) {
        http_response_code(400);
        $error = 'Your session expired. Please refresh the page and try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Enter your username and password.';
    } else {
        $userModel = new User();
        $user = $userModel->login($username, $password);

        if ($user !== false) {
            if (authenticateUser($user)) {
                header('Location: index.php');
                exit;
            }
            $error = 'Your login was accepted, but we could not start a secure session. Please try again.';
        } else {
            $error = 'Login failed. Check that you used the username and password you registered with. If you are new here, create an account below.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In | Library Loan Management</title>
    <style>
        :root { color-scheme: light; --ink: #172c3d; --muted: #718096; --accent: #d39b52; --line: #e5e9ed; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 32px 20px; background: #eef2f3; color: var(--ink); font-family: Arial, sans-serif; }
        .auth-layout { width: min(100%, 1000px); min-height: 590px; display: grid; grid-template-columns: .9fr 1.1fr; overflow: hidden; background: #fff; border: 1px solid #e2e7e9; border-radius: 20px; box-shadow: 0 24px 70px rgba(27, 47, 59, .13); }
        .welcome { position: relative; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; padding: 42px; background: linear-gradient(145deg, #18364a, #102535); color: #fff; }
        .welcome::after { position: absolute; right: -125px; bottom: 55px; width: 330px; height: 330px; border: 1px solid rgba(255,255,255,.12); border-radius: 50%; box-shadow: 0 0 0 35px rgba(255,255,255,.035), 0 0 0 70px rgba(255,255,255,.025); content: ""; }
        .brand { position: relative; z-index: 1; display: flex; align-items: center; gap: 12px; font-size: .78rem; font-weight: 800; letter-spacing: .13em; }
        .brand-mark { display: grid; width: 40px; height: 40px; place-items: center; border: 1px solid rgba(255,255,255,.3); border-radius: 12px; color: #f1c27d; font-size: 1.2rem; }
        .welcome-copy { position: relative; z-index: 1; max-width: 340px; padding-bottom: 24px; }
        .eyebrow { margin: 0 0 12px; color: #edc17e; font-size: .72rem; font-weight: 800; letter-spacing: .15em; text-transform: uppercase; }
        .welcome h2 { margin: 0 0 14px; font-family: Georgia, serif; font-size: clamp(2rem, 4vw, 3.1rem); font-weight: 400; line-height: 1.12; }
        .welcome-copy > p:last-child { margin: 0; color: #c0d0d7; font-size: .96rem; line-height: 1.7; }
        .welcome-foot { position: relative; z-index: 1; color: #a8bac3; font-size: .78rem; }
        main { align-self: center; width: 100%; max-width: 500px; margin: 0 auto; padding: 54px 58px; }
        h1 { margin: 0 0 8px; color: var(--ink); font-size: 1.9rem; letter-spacing: -.04em; }
        .intro { margin: 0 0 30px; color: var(--muted); font-size: .93rem; line-height: 1.6; }
        form { display: grid; gap: 18px; }
        label { display: grid; gap: 8px; color: #34485a; font-size: .82rem; font-weight: 700; }
        input { width: 100%; min-height: 48px; padding: 12px 14px; border: 1px solid var(--line); border-radius: 9px; outline: none; background: #fbfcfc; color: var(--ink); font: inherit; transition: border-color .18s, box-shadow .18s; }
        input:focus { border-color: #7996a6; box-shadow: 0 0 0 3px rgba(73, 119, 143, .12); }
        button { min-height: 49px; margin-top: 4px; border: 0; border-radius: 9px; background: var(--ink); color: white; font: inherit; font-size: .92rem; font-weight: 700; cursor: pointer; transition: background .18s, transform .18s; }
        button:hover { transform: translateY(-1px); background: #23475c; }
        .error { margin: 0 0 20px; padding: 12px 14px; border: 1px solid #f3caca; border-radius: 9px; background: #fff2f1; color: #9c3935; font-size: .88rem; line-height: 1.5; }
        .footer { margin-top: 25px; color: var(--muted); text-align: center; font-size: .86rem; }
        a { color: #a66f2d; font-weight: 700; text-decoration: none; }
        a:hover { text-decoration: underline; }
        @media (max-width: 720px) { body { padding: 16px; } .auth-layout { max-width: 480px; min-height: auto; grid-template-columns: 1fr; } .welcome { min-height: 205px; padding: 25px 28px; } .welcome-copy { padding: 34px 0 0; } .welcome h2 { font-size: 2rem; } .welcome-copy > p:last-child, .welcome-foot { display: none; } main { padding: 34px 28px 38px; } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition: none !important; } }
    </style>
</head>
<body>
<div class="auth-layout">
    <aside class="welcome">
        <div class="brand"><span class="brand-mark" aria-hidden="true">L</span><span>LIBRARY<br>MANAGEMENT</span></div>
        <div class="welcome-copy">
            <p class="eyebrow">Your library workspace</p>
            <h2>Good to have you back.</h2>
            <p>Sign in to keep your books and loan records organized.</p>
        </div>
        <div class="welcome-foot">A calmer way to manage every loan.</div>
    </aside>
    <main>
        <h1>Welcome back</h1>
        <p class="intro">Enter your account details to continue.</p>
        <?php if ($error !== ''): ?><div class="error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <form method="post" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(createCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <label>Username<input name="username" required autocomplete="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
            <button type="submit">Log In</button>
        </form>
        <div class="footer">New to the library? <a href="register.php">Create an account</a></div>
    </main>
</div>
</body>
</html>
