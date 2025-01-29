<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin.php");
    exit();
}

// Firebase initialization
require_once 'vendor/autoload.php';  // Include the Firebase SDK
use Kreait\Firebase\Factory;

$firebase_credentials_path = 'C:/xampp/htdocs/project/library-management-syste-d7b1f-firebase-adminsdk-fbsvc-da41c47c9e.json';

try {
    // Initialize Firebase with the correct path and specify the database URL
    $firebase = (new Factory)
        ->withServiceAccount($firebase_credentials_path)
        ->withDatabaseUri('https://library-management-syste-d7b1f-default-rtdb.asia-southeast1.firebasedatabase.app/');
} catch (Exception $e) {
    echo 'Error initializing Firebase: ' . $e->getMessage();
    exit();
}

// Get a reference to the Firebase Realtime Database
$database = $firebase->createDatabase();

// Handle Book Return
if (isset($_POST['return_book'])) {
    $issue_id = $_POST['issue_id'];

    // Fetch the book_id, due_date and issue_date from the issued_books table
    $issued_books_ref = $database->getReference('issued_books/' . $issue_id);
    $issued_book = $issued_books_ref->getValue();
    
    if ($issued_book && empty($issued_book['return_date'])) {
        $book_id = $issued_book['book_id'];
        $due_date = $issued_book['due_date'];
        $issue_date = $issued_book['issue_date'];

        // Calculate the fine if the book is returned late
        $return_date = date("Y-m-d");
        $fine = 0;

        if ($return_date > $due_date) {
            // Calculate the number of overdue days
            $overdue_days = (strtotime($return_date) - strtotime($due_date)) / (60 * 60 * 24);
            // Fine is $1 per overdue day
            $fine = $overdue_days * 1; // You can change the rate here
        }

        // Update the return date and fine in the issued_books table
        $sql_return = [
            'return_date' => $return_date,
            'fine' => $fine
        ];

        $issued_books_ref->update($sql_return);

        // Update the quantity of the returned book in books table
        $book_ref = $database->getReference('books/' . $book_id);
        $book = $book_ref->getValue();
        if ($book) {
            $new_quantity = $book['quantity'] + 1;
            $book_ref->update(['quantity' => $new_quantity]);
        }

        $message = "Book returned successfully! Fine: $$fine";
    } else {
        $message = "Issued book not found or already returned!";
    }
}

// Fetch all issued books from Firebase
$issued_books_ref = $database->getReference('issued_books');
$issued_books = $issued_books_ref->getValue();

// Filter books that have not been returned (return_date is null)
$issued_books_not_returned = [];
if ($issued_books) {
    foreach ($issued_books as $issue_id => $issued_book) {
        if (empty($issued_book['return_date'])) {  // Check if return_date is null or empty
            $issued_books_not_returned[$issue_id] = $issued_book;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Book - Library Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container my-4">
    <h2>Return Book</h2>
    <?php if (isset($message)) echo "<div class='alert alert-info'>$message</div>"; ?>
    <!-- Back Arrow to Dashboard -->
    <a href="dashboard.php" class="btn btn-info mb-4"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <form method="POST">
        <div class="mb-3">
            <label for="issue_id" class="form-label">Select Issued Book</label>
            <select class="form-select" name="issue_id" required>
                <option value="" disabled selected>Select an issued book</option>
                <?php 
                if (!empty($issued_books_not_returned)) {
                    foreach ($issued_books_not_returned as $issue_id => $issued_book) {
                        $book_id = $issued_book['book_id'];  // Assuming you store book ID, you can fetch the title from the books node
                        $book_ref = $database->getReference('books/' . $book_id);
                        $book = $book_ref->getValue();
                        $book_title = $book ? $book['title'] : "Unknown Book";

                        $user_ref = $database->getReference('users/' . $issued_book['user_id']);
                        $user = $user_ref->getValue();
                        $username = $user ? $user['username'] : "Unknown User";

                        echo "<option value='$issue_id'>
                                $book_title - $username (Issued on: {$issued_book['issue_date']}, Due on: {$issued_book['due_date']})
                              </option>";
                    }
                } else {
                    echo "<option value='' disabled>No books to return</option>";
                }
                ?>
            </select>
        </div>

        <button type="submit" name="return_book" class="btn btn-primary">Return Book</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
