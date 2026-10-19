<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'user.php';

if (isAuthenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';
$firstName = '';
$lastName = '';
$email = '';
$username = '';
$contactNumber = '';
$gender = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim(postString('first_name'));
    $lastName = trim(postString('last_name'));
    $email = trim(postString('email'));
    $username = trim(postString('username'));
    $password = postString('password');
    $contactNumber = trim(postString('contact_number'));
    $gender = postString('gender');
    $address = trim(postString('address'));

    if (!isValidCsrfToken(postString('csrf_token'))) {
        http_response_code(400);
        $error = 'Your session expired. Please refresh the page and try again.';
    } elseif ($firstName === '' || $lastName === '' || $email === '' || $username === '') {
        $error = 'Please complete your first name, last name, email, and username.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $error = 'Enter a valid email address (150 characters or fewer).';
    } elseif (strlen($username) > 100) {
        $error = 'Username must be 100 characters or fewer.';
    } elseif (strlen($firstName) > 100 || strlen($lastName) > 100) {
        $error = 'First name and last name must each be 100 characters or fewer.';
    } elseif (strlen($contactNumber) > 20) {
        $error = 'Contact number must be 20 characters or fewer.';
    } elseif (!in_array($gender, ['', 'Female', 'Male', 'Prefer not to say'], true)) {
        $error = 'Choose one of the available gender options.';
    } elseif (strlen($password) < 6) {
        $error = 'Your password must be at least 6 characters long.';
    } else {
        $userModel = new User();
        $fullName = trim($firstName . ' ' . $lastName);
        $userModel->full_name = $fullName;
        $userModel->first_name = $firstName;
        $userModel->last_name = $lastName;
        $userModel->username = $username;
        $userModel->email = $email;
        $userModel->password = $password;
        $userModel->contact_number = $contactNumber !== '' ? $contactNumber : null;
        $userModel->gender = $gender !== '' ? $gender : null;
        $userModel->address = $address !== '' ? $address : null;
        $userModel->role = 'staff';
        $userId = $userModel->register();

        if ($userId !== false) {
            $authenticated = authenticateUser([
                'user_id' => $userId,
                'username' => $username,
                'full_name' => $fullName,
                'role' => 'staff',
            ]);
            if ($authenticated) {
                header('Location: index.php');
                exit;
            }
            $error = 'Your account was created, but we could not start a secure session. Please log in.';
        } else {
            if ($userModel->usernameExists($username)) {
                $error = 'That username is already registered. Please choose a different username.';
            } elseif ($userModel->emailExists($email)) {
                $error = 'That email address is already registered. Please log in or use another email.';
            } else {
                $error = 'The account could not be saved. Please try again later.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Library Loan Management</title>
    <style>
        :root { color-scheme: light; --ink: #172c3d; --muted: #718096; --accent: #d39b52; --line: #e5e9ed; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 32px 20px; background: #eef2f3; color: var(--ink); font-family: Arial, sans-serif; }
        .auth-layout { width: min(100%, 1120px); min-height: 690px; display: grid; grid-template-columns: .78fr 1.22fr; overflow: hidden; background: #fff; border: 1px solid #e2e7e9; border-radius: 20px; box-shadow: 0 24px 70px rgba(27, 47, 59, .13); }
        .welcome { position: relative; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; padding: 42px; background: linear-gradient(145deg, #18364a, #102535); color: #fff; }
        .welcome::after { position: absolute; right: -145px; bottom: 100px; width: 350px; height: 350px; border: 1px solid rgba(255,255,255,.12); border-radius: 50%; box-shadow: 0 0 0 35px rgba(255,255,255,.035), 0 0 0 70px rgba(255,255,255,.025); content: ""; }
        .brand { position: relative; z-index: 1; display: flex; align-items: center; gap: 12px; font-size: .78rem; font-weight: 800; letter-spacing: .13em; }
        .brand-mark { display: grid; width: 40px; height: 40px; place-items: center; border: 1px solid rgba(255,255,255,.3); border-radius: 12px; color: #f1c27d; font-size: 1.2rem; }
        .welcome-copy { position: relative; z-index: 1; max-width: 330px; padding-bottom: 28px; }
        .eyebrow { margin: 0 0 12px; color: #edc17e; font-size: .72rem; font-weight: 800; letter-spacing: .15em; text-transform: uppercase; }
        .welcome h2 { margin: 0 0 14px; font-family: Georgia, serif; font-size: clamp(2rem, 4vw, 3.1rem); font-weight: 400; line-height: 1.12; }
        .welcome-copy > p:last-child { margin: 0; color: #c0d0d7; font-size: .96rem; line-height: 1.7; }
        .welcome-foot { position: relative; z-index: 1; color: #a8bac3; font-size: .78rem; }
        main { align-self: center; width: 100%; max-width: 700px; margin: 0 auto; padding: 42px 52px; }
        h1 { margin: 0 0 8px; color: var(--ink); font-size: 1.9rem; letter-spacing: -.04em; }
        .intro { margin: 0 0 25px; color: var(--muted); font-size: .93rem; line-height: 1.6; }
        form.grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 15px; }
        label { display: grid; gap: 7px; color: #34485a; font-size: .82rem; font-weight: 700; }
        input, select, textarea { width: 100%; min-height: 46px; padding: 11px 13px; border: 1px solid var(--line); border-radius: 9px; outline: none; background: #fbfcfc; color: var(--ink); font: inherit; transition: border-color .18s, box-shadow .18s; }
        textarea { min-height: 82px; resize: vertical; }
        input:focus, select:focus, textarea:focus { border-color: #7996a6; box-shadow: 0 0 0 3px rgba(73, 119, 143, .12); }
        .wide { grid-column: 1 / -1; }
        button { min-height: 48px; margin-top: 3px; border: 0; border-radius: 9px; background: var(--ink); color: white; font: inherit; font-size: .92rem; font-weight: 700; cursor: pointer; transition: background .18s, transform .18s; }
        button:hover { transform: translateY(-1px); background: #23475c; }
        .error { margin: 0 0 18px; padding: 12px 14px; border: 1px solid #f3caca; border-radius: 9px; background: #fff2f1; color: #9c3935; font-size: .88rem; line-height: 1.5; }
        .footer { margin-top: 22px; color: var(--muted); text-align: center; font-size: .86rem; }
        a { color: #a66f2d; font-weight: 700; text-decoration: none; }
        a:hover { text-decoration: underline; }
        @media (max-width: 800px) { body { padding: 16px; } .auth-layout { max-width: 600px; grid-template-columns: 1fr; } .welcome { min-height: 205px; padding: 25px 28px; } .welcome-copy { padding: 30px 0 0; } .welcome h2 { font-size: 2rem; } .welcome-copy > p:last-child, .welcome-foot { display: none; } main { padding: 32px 28px 38px; } }
        @media (max-width: 520px) { form.grid { grid-template-columns: 1fr; } .wide { grid-column: auto; } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition: none !important; } }
    </style>
</head>
<body>
<div class="auth-layout">
    <aside class="welcome">
        <div class="brand"><span class="brand-mark" aria-hidden="true">L</span><span>LIBRARY<br>MANAGEMENT</span></div>
        <div class="welcome-copy">
            <p class="eyebrow">Start your library journey</p>
            <h2>A little more organized starts here.</h2>
            <p>Create your account to manage book loans and keep your library details in one place.</p>
        </div>
        <div class="welcome-foot">Your books. Your records. All in one place.</div>
    </aside>
    <main>
        <h1>Create your account</h1>
        <p class="intro">Add your details below. Contact number, gender, and address are optional.</p>
        <?php if ($error !== ''): ?><div class="error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <form class="grid" method="post" action="register.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(createCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <label>First name<input name="first_name" required maxlength="100" autocomplete="given-name" value="<?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Last name<input name="last_name" required maxlength="100" autocomplete="family-name" value="<?= htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="wide">Email address<input type="email" name="email" required maxlength="150" autocomplete="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Username<input name="username" required maxlength="100" autocomplete="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Password <span style="font-weight:400;color:var(--muted)">(minimum 6 characters)</span><input type="password" name="password" required minlength="6" autocomplete="new-password"></label>
            <label>Contact number <span style="font-weight:400;color:var(--muted)">(optional)</span><input type="tel" name="contact_number" maxlength="20" autocomplete="tel" value="<?= htmlspecialchars($contactNumber, ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Gender <span style="font-weight:400;color:var(--muted)">(optional)</span><select name="gender"><option value="">Select (optional)</option><?php foreach (['Female', 'Male', 'Prefer not to say'] as $option): ?><option value="<?= $option ?>" <?= $gender === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?></select></label>
            <label class="wide">Address <span style="font-weight:400;color:var(--muted)">(optional)</span><textarea name="address" rows="3"><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></textarea></label>
            <button class="wide" type="submit">Create Account</button>
        </form>
        <div class="footer">Already have an account? <a href="login.php">Log in</a></div>
    </main>
</div>
</body>
</html>
