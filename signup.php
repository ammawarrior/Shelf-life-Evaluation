<?php
session_start();
include 'db.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = isset($_POST['role']) ? intval($_POST['role']) : 2;
    $code_name = trim($_POST['code_name']);
    
    // Handle file upload
    $user_picture = "";
    if (isset($_FILES['user_picture']) && $_FILES['user_picture']['error'] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES['user_picture']['name']);
        if (move_uploaded_file($_FILES['user_picture']['tmp_name'], $target_file)) {
            $user_picture = $target_file;
        }
    }

    // Check if username or email already exists
    $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $checkStmt->bind_param("ss", $username, $email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $error = "Username or email already exists.";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into the database
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, code_name, user_picture) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiss", $username, $email, $hashed_password, $role, $code_name, $user_picture);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Account successfully created. You can now login.";
            header("Location: login.php");
            exit();
        } else {
            $error = "Error in registration. Please try again.";
        }

        $stmt->close();
    }
    
    $checkStmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <link rel="icon" type="image/png" href="assets/img/dost.png">
    <style>
        body {
            background: linear-gradient(-45deg, #0d4e86, #1A2980, #26D0CE);
            background-size: 400% 400%;
            animation: gradientAnimation 8s ease infinite;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            overflow: hidden;
        }

        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-container {
            width: 100%;
            max-width: 320px;
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

        .form-group input, .form-group select {
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

        .form-group input:focus, .form-group select:focus {
            background: rgba(255, 255, 255, 0.4);
        }
        .form-group select option {
    background: #1A2980; /* Dark theme color */
    color: white;
}
        

        .button-group {
            display: flex;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(-45deg, #0d4e86, #1A2980, #26D0CE);
            color: white;
            border: none;
            padding: 14px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            text-align: center;
            text-decoration: none;
        }

        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }

        .btn-primary:active {
            transform: scale(0.97);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h2 style="color: white;">Create an Account</h2>
            <?php if (!empty($error)) echo "<p class='error-message'>$error</p>"; ?>
            <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
    <label for="role">Role</label>
    <select name="role" required>
        <option value="3">Panelist</option>
        <option value="1">Admin</option>
        <option value="2">Analyst</option>
    </select>
</div>


            
            
            <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" required>
                </div>
    
                <div class="form-group">
                    <label for="code_name">Code Name</label>
                    <input type="text" name="code_name">
                </div>
                <div class="form-group">
                    <label for="user_picture">User Picture</label>
                    <input type="file" name="user_picture" accept="image/*">
                </div>
                <div class="button-group">
                    <button type="submit" class="btn-primary">Create Account</button></>
                    <a href="login.php" class="btn-primary">Back</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>