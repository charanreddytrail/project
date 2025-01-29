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
    ->withServiceAccount('C:/xampp/htdocs/project/library-management-syste-d7b1f-firebase-adminsdk-fbsvc-da41c47c9e.json')  // Correct path
    ->withDatabaseUri('https://library-management-syste-d7b1f-default-rtdb.asia-southeast1.firebasedatabase.app'); // Correct Firebase Database URL

// Access Firebase Database
$database = $firebase->createDatabase();

// Handle Add Book
if (isset($_POST['add_book'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $genre = $_POST['genre'];
    $publication_year = $_POST['publication_year'];
    $quantity = $_POST['quantity'];

    // Push data to Firebase
    $newBookRef = $database->getReference('books')->push([
        'title' => $title,
        'author' => $author,
        'genre' => $genre,
        'publication_year' => $publication_year,
        'quantity' => $quantity
    ]);

    $message = "Book added successfully!";
}

// Handle Edit Book
if (isset($_POST['edit_book'])) {
    $book_id = $_POST['book_id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $genre = $_POST['genre'];
    $publication_year = $_POST['publication_year'];
    $quantity = $_POST['quantity'];

    // Update book details in Firebase
    $database->getReference('books/' . $book_id)->update([
        'title' => $title,
        'author' => $author,
        'genre' => $genre,
        'publication_year' => $publication_year,
        'quantity' => $quantity
    ]);

    $message = "Book updated successfully!";
}

// Handle Delete Book
if (isset($_GET['delete'])) {
    $book_id = $_GET['delete'];
    // Remove book from Firebase
    $database->getReference('books/' . $book_id)->remove();
    $message = "Book deleted successfully!";
}

// Fetch all books for display
$booksReference = $database->getReference('books');
$books = $booksReference->getValue();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for the arrow icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-light">

<div class="container">
    <h2 class="my-4">Manage Books</h2>
    
    <!-- Back Arrow to Dashboard -->
    <a href="dashboard.php" class="btn btn-info mb-4"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

    <?php if (isset($message)) echo "<div class='alert alert-info'>$message</div>"; ?>

    <!-- Add Book Form -->
    <div class="card p-4 mb-4">
        <h4>Add New Book</h4>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" class="form-control" name="title" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Author</label>
                <input type="text" class="form-control" name="author" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Genre</label>
                <input type="text" class="form-control" name="genre" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Publication Year</label>
                <input type="number" class="form-control" name="publication_year" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Quantity</label>
                <input type="number" class="form-control" name="quantity" required>
            </div>
            <button type="submit" name="add_book" class="btn btn-primary">Add Book</button>
        </form>
    </div>

    <!-- Books List -->
    <h4>All Books</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Genre</th>
                <th>Year</th>
                <th>Quantity</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($books) {
                foreach ($books as $book_id => $book) { 
            ?>
                <tr>
                    <td><?php echo $book['title']; ?></td>
                    <td><?php echo $book['author']; ?></td>
                    <td><?php echo $book['genre']; ?></td>
                    <td><?php echo $book['publication_year']; ?></td>
                    <td><?php echo $book['quantity']; ?></td>
                    <td>
                        <a href="edit_book.php?book_id=<?php echo $book_id; ?>" class="btn btn-warning">Edit</a>
                        <a href="?delete=<?php echo $book_id; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php 
                }
            } else {
                echo "<tr><td colspan='6'>No books available</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
