<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php'; // Ensure database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user details from the database
$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT code_name, user_picture FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$query->bind_result($code_name, $user_picture);
$query->fetch();
$query->close();

// Default avatar
$default_image = "uploads/default.png";

// Ensure correct image path
if (!empty($user_picture)) {
    // Ensure the database stores only the filename (e.g., "pic.jpg")
    if (!str_starts_with($user_picture, "uploads/")) {
        $user_picture = "uploads/" . $user_picture;
    }
} else {
    $user_picture = $default_image;
}

// Check if the image file exists; otherwise, use the default
if (!file_exists(__DIR__ . "/../" . $user_picture)) {
    $user_picture = $default_image;
}
?>

<div class="navbar-bg"></div>

<!-- Start app top navbar -->
<nav class="navbar navbar-expand-lg main-navbar">
    <form class="form-inline mr-auto">
        <ul class="navbar-nav mr-3">
            <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i class="fas fa-bars"></i></a></li>
            <li><a href="#" data-toggle="search" class="nav-link nav-link-lg d-sm-none"><i class="fas fa-search"></i></a></li>
        </ul>
    </form>
    <ul class="navbar-nav navbar-right">
    <li class="dropdown">
    <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user d-flex align-items-center">
        <img src="<?php echo htmlspecialchars($user_picture); ?>" 
             class="rounded-circle mr-2" 
             style="width: 50px; height: 50px; object-fit: cover;" 
             alt="User Avatar">
             
        <span class="d-sm-inline d-none font-weight-bold" style="font-size: 14px;"> Hi, 
            <?php echo htmlspecialchars($code_name); ?>
        </span>
        
    </a>
    <div class="dropdown-menu dropdown-menu-right">
        <a href="logout.php" class="dropdown-item has-icon text-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</li>
    </ul>
</nav>
