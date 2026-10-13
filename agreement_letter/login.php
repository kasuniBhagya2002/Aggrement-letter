<?php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = "Username and password required";
    } else {

        $stmt = $conn->prepare("SELECT id FROM user WHERE username = ? AND password = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username']  = $username;

            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid username or password";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:"Segoe UI", sans-serif;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;

    /* ✅ GRAY + WHITE MIX */
    background:linear-gradient(135deg,#f3f4f6,#e5e7eb);
}

/* ===== LOGIN CARD ===== */
.login-card{
    background:#ffffff;
    width:100%;
    max-width:380px;
    padding:36px 32px;
    border-radius:16px;
    box-shadow:0 20px 45px rgba(0,0,0,.12);
    animation:fadeIn .6s ease;
}

@keyframes fadeIn{
    from{opacity:0; transform:translateY(15px)}
    to{opacity:1; transform:translateY(0)}
}

.login-card h2{
    margin:0 0 25px;
    text-align:center;
    color:#111827;
    font-size:22px;
    font-weight:700;
}

/* ===== INPUTS ===== */
.form-group{
    margin-bottom:16px;
}

input{
    width:100%;
    padding:14px;
    border-radius:10px;
    border:1px solid #d1d5db;
    font-size:15px;
    outline:none;
    background:#f9fafb;
    transition:.2s;
}

input:focus{
    border-color:#6b7280;
    background:#ffffff;
    box-shadow:0 0 0 3px rgba(0,0,0,.08);
}

/* ===== BUTTON ===== */
button{
    width:100%;
    padding:14px;
    border:none;
    border-radius:12px;
    background:#111827;   /* dark gray */
    color:#ffffff;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
    transition:.2s;
}

button:hover{
    background:#000000;
}

/* ===== ERROR ===== */
.error{
    margin-top:15px;
    padding:10px;
    background:#fef2f2;
    color:#991b1b;
    border-radius:10px;
    text-align:center;
    font-size:14px;
}
</style>
</head>

<body>

<form method="post" class="login-card">

    <h2>System Login</h2>

    <div class="form-group">
        <input type="text" name="username" placeholder="Username" required>
    </div>

    <div class="form-group">
        <input type="password" name="password" placeholder="Password" required>
    </div>

    <button type="submit">Login</button>

    <?php if($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

</form>

</body>
</html>