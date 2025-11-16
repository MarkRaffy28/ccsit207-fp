<?php
  session_start();

  include("config.php");
  include("components.php");

  if(!isset($_SESSION["id"]) || !isset($_GET["book_id"])) {
    header ("Location: index.php");
    exit;
  } elseif($_SESSION["id"] == "0") {
    header ("Location: admin_dashboard.php");
    exit;
  }

  if (isset($_GET["book_id"])) {
    $stmt_show_book_info = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt_show_book_info->bind_param("i", $_GET["book_id"]);
    $stmt_show_book_info->execute();
    $result = $stmt_show_book_info->get_result();

    $row = $result->fetch_assoc();
  }

  showHeader("Book Information");
?>

<main class="m-4" style="min-height: auto !important">
  <?= showAlert(); ?>
  <section class="my-5 d-flex justify-content-center align-items-center">
    <?php if ($result->num_rows > 0): ?>
      <div class="row w-100">
        <div class="col-12 col-lg-4 my-auto text-center">
          <?php if (!empty($row["image"])): ?>
            <img src="data:image/jpeg;base64,<?= base64_encode($row["image"]); ?>" class="rounded">
          <?php else: ?>
            <p class="m-auto text-muted">No Image</p>
          <?php endif; ?>
          <div>
            <?php if (isset($_SESSION["id"])): ?>
              <a id="reserve_link" class="btn btn-sm btn-success mt-3 px-5">Reserve</a>
            <?php else: ?>
              <button class="btn btn-sm btn-success px-5" data-bs-toggle="modal" data-bs-target="#login-prompt">Reserve</button>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-12 col-lg-8 mt-5 mt-lg-0">
          <table class="book-information-table table table-borderless table-hover text-start mx-auto">
            <tr>
              <th>Title:</th>
              <td> <?= $row["title"] ?> </td>
            </tr>
            <tr>
              <th>Description:</th>
              <td> <?= $row["description"] ?> </td>
            </tr>
            <tr>
              <th>Author:</th>
              <td> <?= $row["author"] ?> </td>
            </tr>
            <tr>
              <th>Publisher:</th>
              <td> <?= $row["publisher"] ?> </td>
            </tr>
            <tr>
              <th>Publication Year:</th>
              <td> <?= $row["publication_year"] ?> </td>
            </tr>
            <tr>
              <th>ISBN:</th>
              <td> <?= $row["isbn"] ?> </td>
            </tr>
            <tr>
              <th>Genre:</th>
              <td> <?= $row["genre"] ?> </td>
            </tr>
            <tr>
              <th>Language:</th>
              <td> <?= $row["language"] ?> </td>
            </tr>
            <tr>
              <th>Available Copies:</th>
              <td> <?= $row["available_copies"] ?> </td>
            </tr>
            <tr>
              <th>Total Copies:</th>
              <td> <?= $row["total_copies"] ?> </td>
            </tr>
            <tr>
              <th>Added In:</th>
              <td> <?= date("F j, Y - h:i A", strtotime($row["created_at"])) ?> </td>
            </tr>
          </table>
        </div>
      </div>
    <?php else: ?>
      <div class="row w-100 vh-100">
        <h1 class="text-center text-danger fw-bold">
          <i class="bi bi-exclamation-triangle"></i>
          Book does not exist.
        </h1>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php
  showFooter();
?>