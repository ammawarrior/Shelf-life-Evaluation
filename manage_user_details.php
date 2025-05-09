<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if the user is an Admin
if (!in_array($_SESSION['role'], [1, 6, 7])) {

    header("Location: index.php");
    exit();
}

// Include database connection
include 'db.php';

// Check if user_id is provided in the URL
if (!isset($_GET['id'])) {
    header("Location: manage_user.php");
    exit();
}

$user_id = $_GET['id'];

// Fetch user details from the database
$query = "SELECT user_id, username, email, role, code_name, user_picture, password FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Role mapping
$roles = [
    1 => 'Admin',
    2 => 'Analyst',
    3 => 'Panelist'
];

// Handle form submission for update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $code_name = $_POST['code_name'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $user['password'];
    
    // Handle file upload
    if (isset($_FILES['user_picture']) && $_FILES['user_picture']['error'] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES['user_picture']['name']);
        if (move_uploaded_file($_FILES['user_picture']['tmp_name'], $target_file)) {
            $user_picture = $target_file;
            $update_query = "UPDATE users SET username = ?, email = ?, role = ?, code_name = ?, user_picture = ?, password = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssisssi", $username, $email, $role, $code_name, $user_picture, $password, $user_id);
        }
    } else {
        $update_query = "UPDATE users SET username = ?, email = ?, role = ?, code_name = ?, password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssissi", $username, $email, $role, $code_name, $password, $user_id);
    }
    
    $stmt->execute();
    $stmt->close();
    
    header("Location: manage_user.php?success=updated");
    exit();
}

// Handle delete user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $delete_query = "DELETE FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: manage_user.php?success=deleted");
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User Details</title>
    <link rel="icon" type="image/png" href="assets/img/dost.png">
    <style>
        body {
            background: linear-gradient(-45deg, #1A2980, #26D0CE);
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

        .edit-container {
            width: 100%;
            max-width: 400px;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 30px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 15px;
            text-align: center;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 600;
            color: white;
            margin-bottom: 5px;
            display: block;
        }

        .form-group input, .form-group select {
            width: 90%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.3);
            font-size: 16px;
            color: white;
            outline: none;
            text-align: center;
            transition: background-color 0.3s ease-in-out;
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
            justify-content: center;
        }

        .btn-primary, .btn-danger {
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1A2980, #26D0CE);
            color: white;
        }

        .btn-danger {
            background: rgba(255, 68, 68, 0.8);
            color: white;
        }

        .btn-primary:hover, .btn-danger:hover {
            transform: scale(1.05);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <h2 style="color: white;">Edit User Details</h2>
        <?php if ($user): ?>
            <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <?php foreach ($roles as $key => $role_name): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($user['role'] == $key) ? 'selected' : ''; ?>>
                                <?php echo $role_name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password">
                </div>
                <div class="form-group">
                    <label>Code Name</label>
                    <input type="text" name="code_name" value="<?php echo htmlspecialchars($user['code_name']); ?>">
                </div>
                <div class="form-group">
                    <label>Profile Picture</label>
                    <input type="file" name="user_picture" accept="image/*">
                </div>
                <div class="button-group">
                    <a href="manage_user.php" class="btn-primary">Back</a>
                    <button type="submit" name="delete" class="btn-primary" onclick="return confirm('Are you sure?')">Delete</button>
                    <button type="submit" name="update" class="btn-primary">Update</button>
                </div>
            </form>
        <?php else: ?>
            <p style="color: white;">User not found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
