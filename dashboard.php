<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_email'])) {
    header("Location: admin.php");
    exit();
}

// Firebase initialization
require_once 'vendor/autoload.php';  // Include the Firebase SDK
use Kreait\Firebase\Factory;

// Correct path to Firebase service account credentials file
$firebase_credentials_path = 'C:/xampp/htdocs/project/library-management-syste-d7b1f-firebase-adminsdk-fbsvc-da41c47c9e.json';

try {
    // Initialize Firebase with the correct path and specify the database URL
    $firebase = (new Factory)
        ->withServiceAccount($firebase_credentials_path)
        ->withDatabaseUri('https://library-management-syste-d7b1f-default-rtdb.asia-southeast1.firebasedatabase.app/'); // Corrected URL
} catch (Exception $e) {
    // Catch any errors related to Firebase initialization
    echo 'Error initializing Firebase: ' . $e->getMessage();
    exit();
}

// Get a reference to the Firebase Realtime Database
$database = $firebase->createDatabase();

// Fetch statistics (total books, users, and fine)
$total_books_ref = $database->getReference('books');
$total_books = $total_books_ref->getSnapshot()->numChildren();  // Counting total books

$total_users_ref = $database->getReference('users');
$total_users = $total_users_ref->getSnapshot()->numChildren();  // Counting total users

// Fetch total fines collected
$sql_total_fine_ref = $database->getReference('issued_books');
$total_fine = 0;
$issued_books = $sql_total_fine_ref->getValue();

if ($issued_books !== null) {
    foreach ($issued_books as $book) {
        if (isset($book['fine']) && $book['fine'] > 0) {
            $total_fine += $book['fine'];
        }
    }
}

// Fetch recent activity (e.g., most recent book issues)
$recent_activity_ref = $database->getReference('issued_books')
    ->orderByChild('issue_date')
    ->limitToLast(5);
$recent_activity = $recent_activity_ref->getValue();

// Fetch fine details for each user who has been fined
$fine_details = [];
if ($issued_books !== null) {
    foreach ($issued_books as $book) {
        if (isset($book['fine']) && $book['fine'] > 0) {
            // Debugging: Check the current issued book entry
            echo "Processing book issue: <br>";
            echo "User ID: " . $book['user_id'] . "<br>";
            echo "Book ID: " . $book['book_id'] . "<br>";

            // Fetch user details
            $user_ref = $database->getReference('users/' . $book['user_id']);
            $user = $user_ref->getValue();
            // Debugging: Check if user data exists
            if ($user === null) {
                echo "User data not found for user_id: " . $book['user_id'] . "<br>";
                continue;  // Skip to the next book if user not found
            }
            $book_ref = $database->getReference('books/' . $book['book_id']);
            $book_details = $book_ref->getValue();
            // Debugging: Check if book data exists
            if ($book_details === null) {
                echo "Book data not found for book_id: " . $book['book_id'] . "<br>";
                continue;  // Skip to the next fine if book not found
            }

            $book_title = isset($book_details['title']) ? $book_details['title'] : 'Unknown Book Title';
            $username = isset($user['username']) ? $user['username'] : 'Unknown User';

            $fine_details[] = [
                'username' => $username,
                'title' => $book_title,
                'fine' => $book['fine']
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            height: 100vh;
            background-color: #343a40;
            color: white;
            position: fixed;
            width: 250px;
            top: 0;
            left: 0;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            padding: 15px;
            text-decoration: none;
            display: block;
            font-size: 16px;
        }
        .sidebar a:hover {
            background-color: #575d63;
        }
        .content {
            margin-left: 270px;
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
        .logout-btn {
            position: absolute;
            bottom: 20px;
            left: 20px;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h4 class="text-center text-white">LMS Dashboard</h4>
    <a href="dashboard.php">Home</a>
    <a href="manage_books.php">Manage Books</a>
    <a href="manage_users.php">Manage Users</a>
    <a href="issue_books.php">Issue Books</a>
    <a href="return_books.php">Return Books</a>
    <a href="reports.php">Reports</a>
    <a href="register_user.php">Student Registration</a>
    <a href="settings.php">Settings</a>
    <a href="?logout=true" class="btn btn-danger">Logout</a>
</div>

<!-- Main Content -->
<div class="content">
    <h1 class="text-center">Welcome to the Library Management System</h1>

    <div class="row">
        <!-- Total Books Card -->
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-header">Total Books</div>
                <div class="card-body">
                    <h2 class="card-title"><?php echo $total_books; ?></h2>
                </div>
            </div>
        </div>

        <!-- Total Users Card -->
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-header">Total Users</div>
                <div class="card-body">
                    <h2 class="card-title"><?php echo $total_users; ?></h2>
                </div>
            </div>
        </div>

        <!-- Total Fine Collected Card -->
        <div class="col-md-4">
            <div class="card text-white bg-warning">
                <div class="card-header">Total Fine Collected</div>
                <div class="card-body">
                    <h2 class="card-title">$<?php echo number_format($total_fine, 2); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Fine Collection Details -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    Fine Collection Details
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Book Title</th>
                                <th>Fine</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($fine_details)) {
                                foreach ($fine_details as $fine_detail) {
                                    echo '<tr>';
                                    echo '<td>' . $fine_detail['username'] . '</td>';
                                    echo '<td>' . $fine_detail['title'] . '</td>';
                                    echo '<td>$' . number_format($fine_detail['fine'], 2) . '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="3">No fines collected</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Logout Logic -->
<?php
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit();
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
