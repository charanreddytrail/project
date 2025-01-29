<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin.php");
    exit();
}

// Firebase SDK Setup
require 'vendor/autoload.php'; // Ensure Firebase PHP SDK is installed

use Kreait\Firebase\Factory;

// Path to your Firebase credentials file
$firebase = (new Factory)
    ->withServiceAccount('C:/xampp/htdocs/project/library-management-syste-d7b1f-firebase-adminsdk-fbsvc-da41c47c9e.json') // Correct path to your credentials file
    ->withDatabaseUri('https://library-management-syste-d7b1f-default-rtdb.asia-southeast1.firebasedatabase.app'); // Correct Firebase Database URL

// Access Firebase Database
$database = $firebase->createDatabase();

// Handle Book Issuance
if (isset($_POST['issue_book'])) {
    $book_id = $_POST['book_id'];
    $user_id = $_POST['user_id'];
    $issue_date = date("Y-m-d");
    $due_date = date("Y-m-d", strtotime("+7 days"));  // 7 days due date

    // Fetch book details from Firebase
    $bookReference = $database->getReference('books/' . $book_id);
    $book = $bookReference->getValue();

    if ($book) {
        if ($book['quantity'] > 0) {
            // Issue the book
            $issuedBookReference = $database->getReference('issued_books')->push([
                'book_id' => $book_id,
                'user_id' => $user_id,
                'issue_date' => $issue_date,
                'due_date' => $due_date
            ]);

            // Update book quantity
            $database->getReference('books/' . $book_id)
                ->update(['quantity' => $book['quantity'] - 1]);

            $message = "Book issued successfully!";
        } else {
            $message = "Book not available!";
        }
    } else {
        $message = "Book not found!";
    }
}

// Fetch all books and active users from Firebase
$booksReference = $database->getReference('books');
$books = $booksReference->getValue();

$usersReference = $database->getReference('users');
$users = $usersReference->getValue();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Book - Library Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container my-4">
    <h2>Issue Book</h2>
    <?php if (isset($message)) echo "<div class='alert alert-info'>$message</div>"; ?>
    <!-- Back Arrow to Dashboard -->
    <a href="dashboard.php" class="btn btn-info mb-4"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <form method="POST">
        <div class="mb-3">
            <label for="book_id" class="form-label">Select Book</label>
            <select class="form-select" name="book_id" required>
                <option value="" disabled selected>Select a book</option>
                <?php foreach ($books as $book_id => $book): ?>
                    <option value="<?php echo $book_id; ?>"><?php echo $book['title']; ?> - <?php echo $book['author']; ?> (<?php echo $book['quantity']; ?> available)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="user_id" class="form-label">Select User</label>
            <select class="form-select" name="user_id" required>
                <option value="" disabled selected>Select a user</option>
                <?php foreach ($users as $user_id => $user): ?>
                    <option value="<?php echo $user_id; ?>"><?php echo $user['username']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" name="issue_book" class="btn btn-primary">Issue Book</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
