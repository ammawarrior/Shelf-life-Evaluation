<?php
session_start();
include 'db.php'; // Database connection

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Prepare and execute SQL query
    $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $db_username, $hashed_password, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // Store user session data
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $db_username;
            $_SESSION['role'] = $role;

            // Redirect based on role
            if ($role == 1 || $role == 2) {
                header("Location: analyst.php");
                exit();
            } elseif ($role == 3) {
                header("Location: dashboard_p.php");
                exit();
            } else {
                $error = "Unauthorized role.";
            }
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }

    $stmt->close();
}
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/img/dost.png">
    <title>User Login</title>
    <style>
        body {
            background: linear-gradient(-45deg, #0d4e86, #1A2980, #26D0CE);
            background-size: 400% 400%;
            animation: gradientAnimation 20s ease infinite;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
        }

        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-container {
            width: 100%;
            max-width: 330px;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            position: relative;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            backdrop-filter: blur(10px);
            padding: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.3);
            width: 100%;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 600;
            color: white;
            margin-bottom: 5px;
            align-self: flex-start;
        }

        .form-group input {
            width: 300px;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.3);
            font-size: 16px;
            color: white;
            outline: none;
            transition: 0.3s ease-in-out;
            text-align: center;
        }

        .form-group input:focus {
            background: rgba(255, 255, 255, 0.4);
        }

        .btn-primary {
            background: linear-gradient(-45deg, #0d4e86, #1A2980, #26D0CE);
            color: white;
            border: none;
            width: 100%;
            padding: 14px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: scale(1.05);
            background: linear-gradient(-45deg, #0d4e86, #1A2980, #26D0CE);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }

        .btn-primary:active {
            transform: scale(0.97);
        }


        .logo {
    width: 100px;
    margin-bottom: 10px;
    filter: drop-shadow(0px 4px 6px rgba(0, 0, 0, 0.3)); /* Soft glow effect */
    transition: transform 0.3s ease, filter 0.3s ease;
}

.logo:hover {
    transform: scale(1.1);
    filter: drop-shadow(0px 6px 12px rgba(255, 255, 255, 0.4)); /* Enhanced glow */
}

    
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-card">
        <img src="assets/img/dost.png" alt="Logo" class="logo">
            <h2 style="color: white; width: 100%;">Sensory</h2><br>
            <?php if (!empty($error)) echo "<p class='error-message'>$error</p>"; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary">Login</button>
            </form>
        </div>
    </div>
</body>
</html>