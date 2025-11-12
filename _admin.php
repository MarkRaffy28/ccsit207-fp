<?php
// admin_books.php
session_start();

// DATABASE CONNECTION
include("config.php");

// ADD BOOK
if (isset($_POST["add_book"])) {
  $title = $_POST["title"];
  $description = $_POST["description"];
  $author = $_POST["author"];
  $publisher = $_POST["publisher"];
  $year = $_POST["publication_year"];
  $isbnbility = $_POST["availability"];
  $total  = $_POST["isbn"];
  $genre = $_POST["genre"];
  $language = $_POST["language"];
  $availa= $_POST["total_copies"];
  $available = $_POST["available_copies"];
  $image = null;

  $image = null;
  if (!empty($_FILES["image"]["tmp_name"])) {
    $image = file_get_contents($_FILES["image"]["tmp_name"]);
  }

  $stmt = $conn->prepare("INSERT INTO books (image, title, description, author, publisher, publication_year, isbn, genre, language, availability, total_copies, available_copies)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("bssssissssii", $image, $title, $description, $author, $publisher, $year, $isbn, $genre, $language, $availability, $total, $available);
  $stmt->send_long_data(0, $image);
  $stmt->execute();
  $stmt->close();

  $_SESSION["msg"] = "Book added successfully!";
  header("Location: " . $_SERVER["PHP_SELF"]);
  exit;
}

// UPDATE BOOK
if (isset($_POST["update_book"])) {
  $id = $_POST["book_id"];
  $title = $_POST["title"];
  $description = $_POST["description"];
  $author = $_POST["author"];
  $publisher = $_POST["publisher"];
  $year = $_POST["publication_year"];
  $isbn = $_POST["isbn"];
  $genre = $_POST["genre"];
  $language = $_POST["language"];
  $availability = $_POST["availability"];
  $total = $_POST["total_copies"];
  $available = $_POST["available_copies"];

  if (!empty($_FILES["image"]["tmp_name"])) {
    $image = addslashes(file_get_contents($_FILES["image"]["tmp_name"]));
    $conn->query("UPDATE books SET image='$image' WHERE id=$id");
  }

  $sql = "UPDATE books SET title=?, description=?, author=?, publisher=?, publication_year=?, isbn=?, genre=?, language=?, availability=?, total_copies=?, available_copies=? WHERE id=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ssssissssiii", $title, $description, $author, $publisher, $year, $isbn, $genre, $language, $availability, $total, $available, $id);
  $stmt->execute();
  $stmt->close();

  $_SESSION["msg"] = "Book updated successfully!";
  header("Location: " . $_SERVER["PHP_SELF"]);
  exit;
}

// DELETE BOOK
if (isset($_GET["delete"])) {
  $id = $_GET["delete"];
  $conn->query("DELETE FROM books WHERE id=$id");
  $_SESSION["msg"] = "Book deleted successfully!";
  header("Location: " . $_SERVER["PHP_SELF"]);
  exit;
}

// FETCH BOOKS
$books = $conn->query("SELECT * FROM books ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin | Books Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
  <h2 class="text-center mb-3">📚 Books Management</h2>

  <?php if (isset($_SESSION["msg"])): ?>
    <div class="alert alert-success"><?= $_SESSION["msg"]; unset($_SESSION["msg"]); ?></div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-header fw-bold">Add New Book</div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <div class="row g-3">
          <div class="col-md-6">
            <label>Title</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label>Author</label>
            <input type="text" name="author" class="form-control" required>
          </div>
          <div class="col-md-12">
            <label>Description</label>
            <textarea name="description" class="form-control" required></textarea>
          </div>
          <div class="col-md-6">
            <label>Publisher</label>
            <input type="text" name="publisher" class="form-control">
          </div>
          <div class="col-md-3">
            <label>Publication Year</label>
            <input type="number" name="publication_year" class="form-control">
          </div>
          <div class="col-md-3">
            <label>ISBN</label>
            <input type="text" name="isbn" class="form-control">
          </div>
          <div class="col-md-4">
            <label>Genre</label>
            <input type="text" name="genre" class="form-control">
          </div>
          <div class="col-md-4">
            <label>Language</label>
            <input type="text" name="language" class="form-control" value="English">
          </div>
          <div class="col-md-4">
            <label>Availability</label>
            <select name="availability" class="form-select">
              <option>Available</option>
              <option>Reserved</option>
              <option>Borrowed</option>
              <option>Lost</option>
            </select>
          </div>
          <div class="col-md-3">
            <label>Total Copies</label>
            <input type="number" name="total_copies" class="form-control" value="1">
          </div>
          <div class="col-md-3">
            <label>Available Copies</label>
            <input type="number" name="available_copies" class="form-control" value="1">
          </div>
          <div class="col-md-6">
            <label>Image</label>
            <input type="file" name="image" class="form-control">
          </div>
          <div class="col-md-12 text-end">
            <button type="submit" name="add_book" class="btn btn-primary">Add Book</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <h4>📖 Book List</h4>
  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Image</th>
        <th>Title</th>
        <th>Author</th>
        <th>Genre</th>
        <th>Availability</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $books->fetch_assoc()): ?>
      <tr>
        <td><?= $row["id"]; ?></td>
        <td>
          <?php if (!empty($row["image"])): ?>
            <img src="data:image/jpeg;base64,<?= base64_encode($row["image"]); ?>" width="60" height="60" class="rounded">
          <?php else: ?>
            <span class="text-muted">No Image</span>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($row["title"]); ?></td>
        <td><?= htmlspecialchars($row["author"]); ?></td>
        <td><?= htmlspecialchars($row["genre"]); ?></td>
        <td><?= htmlspecialchars($row["availability"] ?? ""); ?></td>
        <td>
          <a href="?edit=<?= $row["id"]; ?>" class="btn btn-sm btn-warning">Edit</a>
          <a href="?delete=<?= $row["id"]; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this book?')">Delete</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>
