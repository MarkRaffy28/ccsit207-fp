<?php
  session_start();

  include("config.php");
  include("components.php");

  $is_logged_in = isset($_SESSION["id"]);

  if(!isset($_GET["book_id"])) {
    header ("Location: index.php");
    exit;
  } elseif(isset($_SESSION["id"]) && $_SESSION["id"] == "0") {
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
            <img src="data:image/jpeg;base64,<?= base64_encode($row["image"]); ?>" class="shadow-lg rounded" width="250px">
          <?php else: ?>
            <p class="m-auto text-muted">No Image</p>
          <?php endif; ?>
          <div>
            <?php if (isset($_SESSION["id"])): ?>
              <a href="reserve_book.php?book_id=<?= $row["id"] ?>" class="btn btn-success mt-3 px-5">Reserve</a>
            <?php else: ?>
              <button class="btn btn-success mt-3 px-5" data-bs-toggle="modal" data-bs-target="#login-prompt">Reserve</button>
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
          <div class="d-flex justify-content-end">
            <?php if (isset($_GET["source"]) && $_GET["source"] == "transactions" && isset($_GET["tab"])): ?>
              <a href="transactions.php?tab=<?= $_GET["tab"] ?>" class="btn btn-danger me-5">Return</a>
            <?php elseif (isset($_GET["source"]) && $_GET["source"] == "transactions"): ?>
              <a href="transactions.php" class="btn btn-danger me-5">Return</a>
            <?php elseif (isset($_GET["source"]) && $_GET["source"] == "fines" && isset($_GET["tab"])): ?>
              <a href="fines.php?tab=<?= $_GET["tab"] ?>" class="btn btn-danger me-5">Return</a>
            <?php elseif (isset($_GET["source"]) && $_GET["source"] == "fines"): ?>
              <a href="fines.php" class="btn btn-danger me-5">Return</a>
            <?php else: ?>
              <a href="index.php" class="btn btn-danger me-5">Return</a>
            <?php endif; ?>
          </div>
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

  <div class="modal fade p-5" id="login-prompt" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content rounded-4 shadow">
        <div class="modal-header border-0">
          <h5 class="modal-title text-center fw-bold">LOGIN REQUIRED!</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>     
        <div class="modal-body text-center">
          <i class="fa-solid fa-user-lock fa-3x text-danger mb-3"></i>
          <p class="text-dark mb-0">You need to log in to access this feature.</p>
        </div>      
        <div class="modal-footer border-0 d-flex justify-content-center">
          <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
          <a href="login.php" class="btn bg-success text-light rounded-3 px-4">Log In</a>
        </div>
      </div>
    </div>
  </div>
</main>

<?php
  showFooter();
?>