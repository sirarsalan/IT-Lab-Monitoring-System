<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>NCR-CET IT Lab — Login</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/tabler-icons.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',sans-serif;background:#0f0e2e;min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;}
body::before{content:'';position:absolute;top:-100px;right:-100px;width:400px;height:400px;background:rgba(99,102,241,.12);border-radius:50%;pointer-events:none;}
body::after{content:'';position:absolute;bottom:-80px;left:-80px;width:300px;height:300px;background:rgba(139,92,246,.1);border-radius:50%;pointer-events:none;}
.card{background:#1e1b4b;border:1px solid rgba(99,102,241,.25);border-radius:16px;width:360px;padding:36px 32px;position:relative;z-index:1;box-shadow:0 32px 80px rgba(0,0,0,.4);}
.logo{display:flex;align-items:center;gap:12px;margin-bottom:28px;}
.logo-icon{width:44px;height:44px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;}
.logo-text{color:#fff;font-size:15px;font-weight:600;line-height:1.3;}
.logo-sub{color:#a5b4fc;font-size:11px;}
h2{font-size:18px;font-weight:600;color:#fff;margin-bottom:4px;}
.subtitle{font-size:12px;color:#6366f1;margin-bottom:24px;}
.form-group{margin-bottom:14px;}
label{display:block;font-size:11px;font-weight:500;color:#a5b4fc;margin-bottom:6px;text-transform:uppercase;letter-spacing:.06em;}
.input-wrap{position:relative;}
.input-wrap i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#4338ca;font-size:16px;}
input[type=text],input[type=password]{width:100%;padding:10px 12px 10px 36px;background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:8px;color:#e0e7ff;font-size:13px;transition:border .15s;}
input[type=text]:focus,input[type=password]:focus{outline:none;border-color:#6366f1;background:rgba(99,102,241,.15);}
input::placeholder{color:#4338ca;}
button[type=submit]{width:100%;padding:11px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;margin-top:4px;letter-spacing:.02em;transition:opacity .15s;}
button[type=submit]:hover{opacity:.88;}
.err{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#fca5a5;font-size:12px;padding:10px 12px;border-radius:8px;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
</style>
</head>
<body>
<?php
session_start();
include 'db.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $username=$_POST['username']??'';
    $password=$_POST['password']??'';
    if(empty($username)||empty($password)){
        $error='Username aur password dono darkar hain.';
    } else {
        $q=$conn->query("SELECT * FROM users WHERE username='$username' AND password='$password' LIMIT 1");
        if($q&&$q->num_rows===1){
            $u=$q->fetch_assoc();
            $_SESSION['user_id']=$u['id'];
            $_SESSION['username']=$u['username'];
            $_SESSION['role']=$u['role'];
            $_SESSION['category']=$u['category']??'';
            $ip=$_SERVER['REMOTE_ADDR'];
            $conn->query("INSERT INTO active_users (user_id,username,login_time,last_activity,ip_address,status) VALUES ('{$u['id']}','{$u['username']}',NOW(),NOW(),'$ip','active')");
            header("Location: index.php"); exit;
        } else {
            $error='Invalid username or password.';
        }
    }
}
?>
<div class="card">
  <div class="logo">
    <div class="logo-icon"><i class="ti ti-server-2"></i></div>
    <div><div class="logo-text">NCR-CET IT Lab</div><div class="logo-sub">network_db system</div></div>
  </div>
  <h2>Welcome back</h2>
  <div class="subtitle">Sign in to continue</div>
  <?php if($error): ?>
  <div class="err"><i class="ti ti-alert-circle" style="font-size:16px;flex-shrink:0"></i><?=htmlspecialchars($error)?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="form-group">
      <label>Username</label>
      <div class="input-wrap">
        <i class="ti ti-user"></i>
        <input type="text" name="username" placeholder="Enter username" autocomplete="username" required>
      </div>
    </div>
    <div class="form-group">
      <label>Password</label>
      <div class="input-wrap">
        <i class="ti ti-lock"></i>
        <input type="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
      </div>
    </div>
    <button type="submit"><i class="ti ti-login" style="margin-right:6px"></i>Sign In</button>
  </form>
</div>
</body>
</html>
