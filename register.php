<?php
include 'db.php';

/* ONE TIME REGISTRATION CHECK */
$check = mysqli_query($conn, "SELECT id FROM admin");
if (mysqli_num_rows($check) > 0) {
    die("Admin already registered! Please login.");
}

if (isset($_POST['register'])) {

    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    /* LOGO UPLOAD */
    $upload_dir = __DIR__ . "/uploads/logo/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $logo_name = $_FILES['logo']['name'];
    $logo_tmp  = $_FILES['logo']['tmp_name'];
    $ext = strtolower(pathinfo($logo_name, PATHINFO_EXTENSION));

    $allowed = ['jpg','jpeg','png','webp'];
    if (!in_array($ext, $allowed)) {
        die("Invalid logo format! Only JPG, PNG, WEBP allowed.");
    }

    $new_logo = time() . "_" . $logo_name;
    $path = $upload_dir . $new_logo;

    if (move_uploaded_file($logo_tmp, $path)) {

        $insert = mysqli_query($conn,
            "INSERT INTO admin (login_logo,email,password)
             VALUES ('$new_logo','$email','$password')"
        );

        if ($insert) {
            echo "<script>
                    alert('Admin Registered Successfully!');
                    window.location.href='index.php';
                  </script>";
            exit;
        } else {
            echo "Database error: " . mysqli_error($conn);
        }

    } else {
        echo "Failed to upload logo!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Registration</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:Arial, sans-serif;}
        body{
            background: linear-gradient(135deg, #6C63FF, #42A5F5);
            height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
        }
        .container{
            background:#fff;
            padding:40px;
            border-radius:15px;
            box-shadow:0 10px 25px rgba(0,0,0,0.2);
            width:400px;
            text-align:center;
        }
        .container h2{
            margin-bottom:20px;
            color:#333;
        }
        .logo-preview{
            width:100px;
            height:100px;
            margin:0 auto 20px;
            border-radius:50%;
            background:#f0f0f0;
            display:flex;
            justify-content:center;
            align-items:center;
            overflow:hidden;
            font-size:14px;
            color:#aaa;
        }
        input[type="file"]{display:block;margin:0 auto 20px;}
        input[type="email"], input[type="password"]{
            width:100%;
            padding:12px 15px;
            margin-bottom:15px;
            border:1px solid #ccc;
            border-radius:8px;
            transition:0.3s;
        }
        input[type="email"]:focus, input[type="password"]:focus{
            border-color:#6C63FF;
            outline:none;
        }
        button{
            width:100%;
            padding:12px;
            background:#6C63FF;
            color:white;
            font-size:16px;
            font-weight:bold;
            border:none;
            border-radius:8px;
            cursor:pointer;
            transition:0.3s;
        }
        button:hover{background:#5548c8;}
        .footer-text{
            margin-top:15px;
            font-size:14px;
            color:#666;
        }
        .footer-text a{
            color:#6C63FF;
            text-decoration:none;
            font-weight:bold;
        }s
    </style>
</head>
<body>

<div class="container">
    <h2>Admin Registration</h2>

    <div class="logo-preview">Logo</div>

    <form method="post" enctype="multipart/form-data">
        <input type="file" name="logo" required><br>
        <input type="email" name="email" placeholder="Email Address" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button name="register">Register</button>
    </form>

    <div class="footer-text">
        Already registered? <a href="login.php">Login here</a>
    </div>
</div>

</body>
</html>
