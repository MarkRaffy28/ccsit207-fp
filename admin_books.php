<?php
// admin_books.php
session_start();

// DATABASE CONNECTION
include("config.php");

// Initialize variables to avoid nulls
$id = $title = $description = $author = $publisher = $year = $isbn = $genre = $language = $availability = "";
$total = $available = 1; // default 1
$image = null;

// EDIT MODE
if (isset($_GET["edit"])) {
    $id = $_GET["edit"];
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($book = $result->fetch_assoc()) {
        $title = $book["title"] ?? "";
        $description = $book["description"] ?? "";
        $author = $book["author"] ?? "";
        $publisher = $book["publisher"] ?? "";
        $year = $book["publication_year"] ?? "";
        $isbn = $book["isbn"] ?? "";
        $genre = $book["genre"] ?? "";
        $language = $book["language"] ?? "English";
        $availability = $book["availability"] ?? "Available";
        $total = $book["total_copies"] ?? 1;
        $available = $book["available_copies"] ?? 1;
        $image = $book["image"] ?? null;
    }
    $stmt->close();
}

// ADD BOOK
if (isset($_POST["add_book"])) {
    $title = $_POST["title"] ?: "";
    $description = $_POST["description"] ?: "";
    $author = $_POST["author"] ?: "";
    $publisher = $_POST["publisher"] ?: "";
    $year = $_POST["publication_year"] ?: 0;
    $isbn = $_POST["isbn"] ?: "";
    $genre = $_POST["genre"] ?: "";
    $language = $_POST["language"] ?: "English";
    $availability = $_POST["availability"] ?: "Available";
    $total = $_POST["total_copies"] ?: 1;
    $available = $_POST["available_copies"] ?: 1;
    $image = !empty($_FILES["image"]["tmp_name"]) ? file_get_contents($_FILES["image"]["tmp_name"]) : null;

    $stmt = $conn->prepare("INSERT INTO books (image, title, description, author, publisher, publication_year, isbn, genre, language, availability, total_copies, available_copies)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("bssssissssii", $image, $title, $description, $author, $publisher, $year, $isbn, $genre, $language, $availability, $total, $available);
    if ($image) $stmt->send_long_data(0, $image);
    $stmt->execute();
    $stmt->close();

    $_SESSION["msg"] = "Book added successfully!";
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

// UPDATE BOOK
if (isset($_POST["update_book"])) {
    $id = $_POST["book_id"];
    $title = $_POST["title"] ?: "";
    $description = $_POST["description"] ?: "";
    $author = $_POST["author"] ?: "";
    $publisher = $_POST["publisher"] ?: "";
    $year = $_POST["publication_year"] ?: 0;
    $isbn = $_POST["isbn"] ?: "";
    $genre = $_POST["genre"] ?: "";
    $language = $_POST["language"] ?: "English";
    $availability = $_POST["availability"] ?: "Available";
    $total = $_POST["total_copies"] ?: 1;
    $available = $_POST["available_copies"] ?: 1;

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
    <div class="card-header fw-bold"><?= $id ? "Edit Book" : "Add New Book" ?></div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="book_id" value="<?= htmlspecialchars($id); ?>">
        <div class="row g-3">
          <div class="col-md-6">
            <label>Title</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($title); ?>" required>
          </div>
          <div class="col-md-6">
            <label>Author</label>
            <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($author); ?>" required>
          </div>
          <div class="col-md-12">
            <label>Description</label>
            <textarea name="description" class="form-control" required><?= htmlspecialchars($description); ?></textarea>
          </div>
          <div class="col-md-6">
            <label>Publisher</label>
            <input type="text" name="publisher" class="form-control" value="<?= htmlspecialchars($publisher); ?>">
          </div>
          <div class="col-md-3">
            <label>Publication Year</label>
            <input type="number" name="publication_year" class="form-control" value="<?= htmlspecialchars($year); ?>">
          </div>
          <div class="col-md-3">
            <label>ISBN</label>
            <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($isbn); ?>">
          </div>
          <div class="col-md-4">
            <label>Genre</label>
            <input type="text" name="genre" class="form-control" value="<?= htmlspecialchars($genre); ?>">
          </div>
          <div class="col-md-4">
            <label>Language</label>
            <input type="text" name="language" class="form-control" value="<?= htmlspecialchars($language); ?>">
          </div>
          <div class="col-md-4">
            <label>Availability</label>
            <select name="availability" class="form-select">
              <?php foreach (["Available","Reserved","Borrowed","Lost"] as $option): ?>
                <option <?= $availability == $option ? "selected" : "" ?>><?= $option ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label>Total Copies</label>
            <input type="number" name="total_copies" class="form-control" value="<?= htmlspecialchars($total); ?>">
          </div>
          <div class="col-md-3">
            <label>Available Copies</label>
            <input type="number" name="available_copies" class="form-control" value="<?= htmlspecialchars($available); ?>">
          </div>
          <div class="col-md-6">
            <label>Image</label>
            <input type="file" name="image" class="form-control">
            <?php if ($image): ?>
              <img src="data:image/jpeg;base64,<?= base64_encode($image); ?>" width="60" height="60" class="rounded mt-2">
            <?php endif; ?>
          </div>
          <div class="col-md-12 text-end">
            <button type="submit" name="<?= $id ? "update_book" : "add_book"; ?>" class="btn btn-primary">
              <?= $id ? "Update Book" : "Add Book"; ?>
            </button>
            <?php if($id): ?>
              <a href="admin_books.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
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
