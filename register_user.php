<?php
session_start();

// Make sure to include the Firebase SDK and its dependencies
require 'vendor/autoload.php';  // Correctly include the Composer autoload file

// Use the Firebase namespace after including the necessary files
use Kreait\Firebase\Factory; // Make sure this line is at the top, directly after the PHP opening tag

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Initialize Firebase
    $firebase = (new Factory)
        ->withServiceAccount('C:/xampp/htdocs/project/library-management-syste-d7b1f-firebase-adminsdk-fbsvc-da41c47c9e.json') // Path to your Firebase credentials file
        ->withDatabaseUri('https://library-management-syste-d7b1f-default-rtdb.asia-southeast1.firebasedatabase.app'); // Your Firebase Realtime Database URL

    // Get the Firebase Database reference
    $database = $firebase->createDatabase();

    // Collect the user data from the form
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);  // Hash the password

    // Prepare the user data array
    $user_data = [
        'username' => $username,
        'email' => $email,
        'password' => $password,
        'status' => 'active'  // Default status is active
    ];

    // Push the data to Firebase's 'users' node
    $user_reference = $database->getReference('users')->push($user_data);

    // Check if the user was added successfully
    if ($user_reference) {
        $message = "User registered successfully!";
    } else {
        $message = "Error: Unable to register user.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration - Library Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container my-4">
    <h2>User Registration</h2>
     <!-- Back Arrow to Dashboard -->
    <a href="dashboard.php" class="btn btn-info mb-4"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

    <?php if (isset($message)) echo "<div class='alert alert-info'>$message</div>"; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" name="username" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary">Register</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
