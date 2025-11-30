<?php
  session_start();

  include("config.php");
  include("components.php");

  if(!isset($_SESSION["id"])) {
    header ("Location: index.php");
    exit;
  } elseif($_SESSION["id"] == "0") {
    header ("Location: admin_dashboard.php");
    exit;
  }

  $user_id = $_SESSION["id"] ?? "";

  if (isset($_POST["edit_reservation"])) {
    $edit_id = $_POST["edit_id"];
    $old_book_id = $_POST["old_book_id"];
    $edit_book_id = test_input($_POST["edit_book_id"]);
    $edit_borrow_date = test_input($_POST["edit_borrow_date"]);
    $edit_return_date = test_input($_POST["edit_return_date"]);
    $edit_due_date = date('Y-m-d', strtotime($edit_return_date . ' +7 days'));

    $stmt_check_reserved_book = $conn->prepare("SELECT id FROM transactions WHERE user_id = ? AND book_id = ? AND status = 'Reserved' AND id != ?");
    $stmt_check_reserved_book->bind_param("iii", $user_id, $edit_book_id, $edit_id);
    $stmt_check_reserved_book->execute();
    $stmt_check_reserved_book->store_result();
    
    if ($stmt_check_reserved_book->num_rows > 0) {
      $_SESSION["msg"] = ["danger", "You already reserved this book. Please complete or cancel it before reserving again."];
      header ("Location: " . $_SERVER["PHP_SELF"]);
      exit;
    }
  
    $stmt_check_borrowed_book = $conn->prepare("SELECT id FROM transactions WHERE user_id = ? AND book_id = ? AND status = 'Borrowed' AND id != ?");
    $stmt_check_borrowed_book->bind_param("iii", $user_id, $edit_book_id, $edit_id);
    $stmt_check_borrowed_book->execute();
    $stmt_check_borrowed_book->store_result();
    
    if ($stmt_check_borrowed_book->num_rows > 0) {
      $_SESSION["msg"] = ["danger", "You already borrowed this book. Please complete or cancel it before reserving again."];
      header ("Location: " . $_SERVER["PHP_SELF"]);
      exit;
    } 

    $stmt_reserve_book = $conn->prepare("UPDATE transactions SET
        book_id = ?, 
        borrow_date = ?, 
        return_date = ?, 
        due_date = ?
      WHERE id = ? AND user_id = ?");
    $stmt_reserve_book->bind_param("isssii", $edit_book_id, $edit_borrow_date, $edit_return_date, $edit_due_date, $edit_id, $user_id);
    
    if (!$stmt_reserve_book->execute()) {
      $_SESSION["msg"] = ["danger", "Update reservation error. Please try again later."];
      header ("Location: " . $_SERVER["PHP_SELF"]);
      exit;
    }

    if ($old_book_id != $edit_book_id) {
      $stmt_inc_book_copy = $conn->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
      $stmt_inc_book_copy->bind_param("i", $old_book_id);
      $stmt_inc_book_copy->execute();

      $stmt_dec_book_copy = $conn->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
      $stmt_dec_book_copy->bind_param("i", $edit_book_id);
      $stmt_dec_book_copy->execute();
    }

    $_SESSION["msg"] = ["success", "Book reservation updated successfully"];
    header ("Location: " . $_SERVER["PHP_SELF"]);
    exit;
  }

  if (isset($_POST["cancel_reservation"])) {
    $cancel_id = $_POST["cancel_id"];
    $cancel_book_id = $_POST["cancel_book_id"];

    $stmt_cancel_reservation = $conn->prepare("UPDATE transactions SET status = 'Cancelled' WHERE id = ? AND user_id = ?");
    $stmt_cancel_reservation->bind_param("ii", $cancel_id, $user_id);
    
    if (!$stmt_cancel_reservation->execute()) {
      $_SESSION["msg"] = ["danger", "Cancellation error. Please try again later."];
      header ("Location: " . $_SERVER["PHP_SELF"]);
      exit;
    }

    $stmt_inc_book_copy = $conn->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
    $stmt_inc_book_copy->bind_param("i", $cancel_book_id);
    $stmt_inc_book_copy->execute();

    $_SESSION["msg"] = ["success", "Reservation cancelled successfully."];
    header ("Location: " . $_SERVER["PHP_SELF"]);
    exit;
  }

  showHeader("Transactions");
?>

<main class="p-4">
  <?= showAlert(); ?>
  <section class="my-3">
    <div class="row d-flex justify-content-between align-items-center gy-3 px-lg-6">
      <h3 class="col-12 col-lg-9 fw-semibold"><i class="bi bi-receipt"></i> Transactions</h3>
      <div class="col-12 col-lg-3">
        <div class="position-relative search-container">
          <input type="text" id="search_input" class="form-control ps-5" placeholder="Search...">
          <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted">
            <i class="bi bi-search"></i>
          </span>
        </div>
      </div>
    </div>
    <div class=" px-lg-6">
      <ul class="nav nav-pills mt-4 border-bottom border-primary" id="transactions_tab" role="tablist">
        <li class="nav-item">
          <button class="nav-link active rounded-top-3 rounded-0 rounded-bottom-0" data-bs-toggle="tab" data-bs-target="#reserved" type="button">Reserved</button>
        </li>
        <li class="nav-item">
          <button class="nav-link rounded-top-3 rounded-0 rounded-bottom-0" data-bs-toggle="tab" data-bs-target="#borrowed" type="button">Borrowed</button>
        </li>
        <li class="nav-item">
          <button class="nav-link rounded-top-3 rounded-0 rounded-bottom-0" data-bs-toggle="tab" data-bs-target="#returned" type="button">Returned</button>
        </li>
        <li class="nav-item">
          <button class="nav-link rounded-top-3 rounded-0 rounded-bottom-0 position-relative" data-bs-toggle="tab" data-bs-target="#overdue" type="button">
            Overdue
            <?php
              $overdue_notif_result = $conn->query("SELECT COUNT(id) FROM transactions WHERE status = 'Overdue' AND user_id = $user_id");
              $overdue_notif_count = $overdue_notif_result->fetch_row()[0]; 
  
              if ($overdue_notif_count > 0) {
                echo "<span class='badge bg-danger rounded-pill position-absolute top-75 start-100 translate-middle fs-7'>
                      $overdue_notif_count
                    </span>";
              }
            ?>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link rounded-top-3 rounded-0 rounded-bottom-0" data-bs-toggle="tab" data-bs-target="#cancelled" type="button">Cancelled</button>
        </li>
        <li class="nav-item">
          <button class="nav-link rounded-top-3 rounded-0 rounded-bottom-0 position-relative" data-bs-toggle="tab" data-bs-target="#lost" type="button">
            Lost
            <?php
              $lost_notif_result = $conn->query("SELECT COUNT(id) FROM transactions WHERE status = 'Lost' AND user_id = $user_id");
              $lost_notif_count = $lost_notif_result->fetch_row()[0]; 
  
              if ($lost_notif_count > 0) {
                echo "<span class='badge bg-danger rounded-pill position-absolute top-75 start-100 translate-middle fs-7'>
                      $lost_notif_count
                    </span>";
              }
            ?>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link rounded-top-3 rounded-0 rounded-bottom-0" data-bs-toggle="tab" data-bs-target="#history" type="button">History</button>
        </li>
      </ul>
    </div>
  </section>

  <section class="tab-content px-lg-6">
    <div class="tab-pane show fade active" id="reserved" role="tabpanel">
      <h5 class="fw-semibold text-center"><i class="bi bi-bookmark-fill"></i> Reserved Book(s)</h5>
      <div class="container my-4">
        <?php
          $stmt_show_reserved = $conn->prepare("SELECT 
              t.*,
              b.id AS book_id,
              b.title,
              b.author
            FROM transactions t
            JOIN books b ON t.book_id = b.id
            WHERE t.user_id = ? AND status = 'Reserved'");
          $stmt_show_reserved->bind_param("i", $_SESSION["id"]);
          $stmt_show_reserved->execute();
          $reserved_result = $stmt_show_reserved->get_result();
          
          if ($reserved_result->num_rows == 0) {
            echo '<p class="text-center text-muted fw-semibold mt-4">No transaction available.</p>';
          }
          while ($reserved_row = $reserved_result->fetch_assoc()):
            $borrow_date = date("F j, Y", strtotime($reserved_row["borrow_date"]));
            $return_date = date("F j, Y", strtotime($reserved_row["return_date"]));
            
        ?>
            <div class="card transaction border-0 shadow-sm rounded-4 mb-3 cursor-pointer"
              data-title="<?= $reserved_row["title"] ?>"
              data-author="<?= $reserved_row["author"] ?>"
              data-reservedate="<?= $reserved_row["reserve_date"] ?>"
              data-borrowdate="<?= $reserved_row["borrow_date"] ?>"
              data-returndate="<?= $reserved_row["return_date"] ?>"
              data-duedate="<?= $reserved_row["due_date"] ?>"
              data-status="<?= $reserved_row["status"] ?>"
            >
                <div class="card-body">
                  <div class="row justify-content-between align-items-center">
                    <div class="col-md-5">
                      <div class="d-flex justify-content-between align-items-center gap-2">
                        <h6 class="mb-0 fw-normal"> 
                          <a href="book_information.php?book_id=<?= $reserved_row["book_id"]; ?>&source=transactions&tab=reserved" class="text-dark fw-semibold link-offset-1 link-underline-dark link-underline-opacity-50 link-underline-opacity-75-hover">
                            <?= $reserved_row["title"] ?> 
                          </a>
                          by
                          <span class="fw-semibold">  <?= $reserved_row["author"] ?></span>
                        </h6>
                      </div>
                    </div>
                    <div class="col-md-4 my-2 my-md-0 d-flex justify-content-lg-end">
                      <small class="text-muted text-start">
                        <span class="d-block"> 
                          Borrow Date: <span class="fw-semibold"><?= "$borrow_date" ?></span>  
                        </span>
                        <span class="d-block"> 
                          Return Date: <span class="fw-semibold"><?= "$return_date" ?></span> 
                        </span>
                      </small>
                    </div>
                    <div class="col-md-3 d-flex justify-content-lg-end align-items-center flex-shrink-0 gap-2 mt-2 mt-md-0">
                      <div class="d-flex gap-2">
                        <button class="edit-button btn btn-sm btn-warning"
                          data-id="<?= $reserved_row["id"] ?>"
                          data-bookid="<?= $reserved_row["book_id"] ?>"
                          data-borrowdate="<?= $reserved_row["borrow_date"] ?>"
                          data-returndate="<?= $reserved_row["return_date"] ?>" 
                          onclick="event.stopPropagation()"                      
                        >
                          <i class="bi bi-pencil me-1"></i>Edit
                        </button>
                        <button class="cancel-button btn btn-sm btn-danger" 
                          data-id="<?= $reserved_row["id"] ?>"
                          data-bookid="<?= $reserved_row["book_id"] ?>"
                          onclick="event.stopPropagation()"      
                        >
                          <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
            </div>
        <?php
          endwhile;
        ?>
      </div>
    </div>

    <div class="tab-pane fade" id="borrowed" role="tabpanel">
      <h5 class="fw-semibold text-center"><i class="bi bi-journal-bookmark-fill"></i> Borrowed Book(s)</h5>
      <div class="container my-4">
        <?php
          $stmt_show_borrowed = $conn->prepare("SELECT 
              t.*,
              b.id AS book_id,
              b.title,
              b.author
            FROM transactions t
            JOIN books b ON t.book_id = b.id
            WHERE t.user_id = ? AND status = 'Borrowed'");
          $stmt_show_borrowed->bind_param("i", $_SESSION["id"]);
          $stmt_show_borrowed->execute();
          $borrowed_result = $stmt_show_borrowed->get_result();
          
          if ($borrowed_result->num_rows == 0) {
            echo '<p class="text-center text-muted fw-semibold mt-4">No transaction available.</p>';
          }
          while ($borrowed_row = $borrowed_result->fetch_assoc()):
            $return_date = date("F j, Y", strtotime($borrowed_row["return_date"]));
            $due_date = date("F j, Y", strtotime($borrowed_row["due_date"]));
        ?>
            <div class="card transaction border-0 shadow-sm rounded-4 mb-3 cursor-pointer"
              data-title="<?= $borrowed_row["title"] ?>"
              data-author="<?= $borrowed_row["author"] ?>"
              data-reservedate="<?= $borrowed_row["reserve_date"] ?>"
              data-borrowdate="<?= $borrowed_row["borrow_date"] ?>"
              data-returndate="<?= $borrowed_row["return_date"] ?>"
              data-duedate="<?= $borrowed_row["due_date"] ?>"
              data-status="<?= $borrowed_row["status"] ?>"
            >
                <div class="card-body">
                  <div class="row justify-content-between align-items-center">
                    <div class="col-md-6">
                      <div class="d-flex justify-content-between align-items-center gap-2">
                        <h6 class="mb-0 fw-normal"> 
                          <a href="book_information.php?book_id=<?= $borrowed_row["book_id"]; ?>&source=transactions&tab=borrowed" class="text-dark fw-semibold link-offset-1 link-underline-dark link-underline-opacity-50 link-underline-opacity-75-hover">
                            <?= $borrowed_row["title"] ?> 
                          </a>
                          by
                          <span class="fw-semibold">  <?= $borrowed_row["author"] ?></span>
                        </h6>
                      </div>
                    </div>
                    <div class="col-md-6 my-2 my-md-0 d-flex flex-column flex-lg-row justify-content-lg-end gap-lg-3">
                      <small class="text-muted text-start">
                        <span class="d-block"> 
                          Return Date: <span class="fw-semibold"><?= $return_date ?></span> 
                        </span>
                        <span class="d-block">
                          Due Date: <span class="fw-bold"><?= $due_date ?></span>
                        </span>
                      </small>
                    </div>
                  </div>
                </div>
            </div>
        <?php
          endwhile;
        ?>
      </div>
    </div>

    <div class="tab-pane fade" id="returned" role="tabpanel">
      <h5 class="fw-semibold text-center"><i class="bi bi-check-circle-fill"></i> Returned Book(s)</h5>
      <div class="container my-4">
        <?php
          $stmt_show_returned = $conn->prepare("SELECT 
              t.*,
              t.fine_amount,
              b.id AS book_id,
              b.title,
              b.author
            FROM transactions t
            JOIN books b ON t.book_id = b.id
            WHERE t.user_id = ? AND status = 'Returned'");
          $stmt_show_returned->bind_param("i", $_SESSION["id"]);
          $stmt_show_returned->execute();
          $returned_result = $stmt_show_returned->get_result();
          
          if ($returned_result->num_rows == 0) {
            echo '<p class="text-center text-muted fw-semibold mt-4">No transaction available.</p>';
          }
          while ($returned_row = $returned_result->fetch_assoc()):
            $borrow_date = date("F j, Y", strtotime($returned_row["borrow_date"]));
            $return_date = date("F j, Y", strtotime($returned_row["return_date"]));
        ?>
            <div class="card transaction border-0 shadow-sm rounded-4 mb-3 cursor-pointer"
              data-title="<?= $returned_row["title"] ?>"
              data-author="<?= $returned_row["author"] ?>"
              data-reservedate="<?= $returned_row["reserve_date"] ?>"
              data-borrowdate="<?= $returned_row["borrow_date"] ?>"
              data-returndate="<?= $returned_row["return_date"] ?>"
              data-duedate="<?= $returned_row["due_date"] ?>"
              data-notes="<?= $returned_row["notes"] ?? ""?>"
              data-fineamount="<?= $returned_row["fine_amount"] ?>"
              data-status="<?= $returned_row["status"] ?>"
            >
                <div class="card-body">
                  <div class="row justify-content-between align-items-center">
                    <div class="col-md-6">
                      <div class="d-flex justify-content-between align-items-center gap-2">
                        <h6 class="mb-0 fw-normal"> 
                          <a href="book_information.php?book_id=<?= $returned_row["book_id"]; ?>&source=transactions&tab=returned" class="text-dark fw-semibold link-offset-1 link-underline-dark link-underline-opacity-50 link-underline-opacity-75-hover">
                            <?= $returned_row["title"] ?> 
                          </a>
                          by
                          <span class="fw-semibold">  <?= $returned_row["author"] ?></span>
                        </h6>
                      </div>
                    </div>
                    <div class="col-md-6 my-2 my-md-0 d-flex flex-column flex-lg-row justify-content-lg-end gap-lg-3">
                      <small class="text-muted text-start">
                        <span class="d-block">
                          Borrow Date: <span class="fw-bold"><?= $borrow_date ?></span>
                        </span>
                        <span class="d-block"> 
                          Return Date: <span class="fw-semibold"><?= $return_date ?></span> 
                        </span>
                      </small>
                    </div>
                  </div>
                </div>
            </div>
        <?php
          endwhile;
        ?>
      </div>
    </div>

    <div class="tab-pane fade" id="overdue" role="tabpanel">
      <h5 class="fw-semibold text-center"><i class="bi bi-exclamation-triangle-fill"></i> Overdue Book(s)</h5>
      <div class="container my-4">
        <?php
          $stmt_show_overdue = $conn->prepare("SELECT 
              t.*,
              t.fine_amount,
              b.id AS book_id,
              b.title,
              b.author
            FROM transactions t
            JOIN books b ON t.book_id = b.id
            WHERE t.user_id = ? AND status = 'Overdue'");
          $stmt_show_overdue->bind_param("i", $_SESSION["id"]);
          $stmt_show_overdue->execute();
          $overdue_result = $stmt_show_overdue->get_result();
          
          if ($overdue_result->num_rows == 0) {
            echo '<p class="text-center text-muted fw-semibold mt-4">No transaction available.</p>';
          }
          while ($overdue_row = $overdue_result->fetch_assoc()):
            $return_date = date("F j, Y", strtotime($overdue_row["return_date"]));
            $due_date = date("F j, Y", strtotime($overdue_row["due_date"]));
            $overdue_id = $overdue_row["id"];
        ?>
            <div class="card border-1 border-danger shadow-sm rounded-4 mb-3 cursor-pointer"
              data-title="<?= $overdue_row["title"] ?>"
              data-author="<?= $overdue_row["author"] ?>"
              data-reservedate="<?= $overdue_row["reserve_date"] ?>"
              data-borrowdate="<?= $overdue_row["borrow_date"] ?>"
              data-returndate="<?= $overdue_row["return_date"] ?>"
              data-duedate="<?= $overdue_row["due_date"] ?>"
              data-notes="<?= $overdue_row["notes"] ?? ""?>"
              data-fineamount="<?= $overdue_row["fine_amount"] ?>"
              data-status="<?= $overdue_row["status"] ?>"
              onclick="window.location.href='fines.php'"
            >
                <div class="card-body">
                  <div class="row justify-content-between align-items-center">
                    <div class="col-md-4">
                      <div class="d-flex justify-content-between align-items-center gap-2">
                        <h6 class="mb-0 fw-normal"> 
                          <a href="book_information.php?book_id=<?= $overdue_row["book_id"]; ?>&source=transactions&tab=overdue" class="text-dark fw-semibold link-offset-1 link-underline-dark link-underline-opacity-50 link-underline-opacity-75-hover">
                            <?= $overdue_row["title"] ?> 
                          </a>
                          by
                          <span class="fw-semibold">  <?= $overdue_row["author"] ?></span>
                        </h6>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="d-flex justify-content-lg-end align-items-center gap-3 mt-2 mt-lg-0">
                        <small class='text-muted text-start'>
                          <span class='d-block'> 
                            Fine Amount: <span class='fw-semibold'>₱<?= $overdue_row["fine_amount"] ?></span> 
                          </span>
                        </small>
                      </div>
                    </div>
                    <div class="col-md-4 my-2 my-md-0 d-flex flex-column flex-lg-row justify-content-lg-end gap-lg-3">
                      <small class="text-muted text-start">
                        <span class="d-block"> 
                          Return Date: <span class="fw-semibold"><?= $return_date ?></span> 
                        </span>
                        <span class="d-block">
                          Due Date: <span class="fw-bold"><?= $due_date ?></span>
                        </span>
                      </small>
                    </div>
                  </div>
                </div>
            </div>
        <?php
          endwhile;
        ?>
      </div>
    </div>

    <div class="tab-pane fade" id="cancelled" role="tabpanel">
      <h5 class="fw-semibold text-center"><i class="bi bi-x-circle-fill"></i> Cancelled Book(s)</h5>
      <div class="container my-4">
        <?php
          $stmt_show_cancelled = $conn->prepare("SELECT 
              t.*,
              t.fine_amount,
              b.id AS book_id,
              b.title,
              b.author
            FROM transactions t
            JOIN books b ON t.book_id = b.id
            WHERE t.user_id = ? AND status = 'Cancelled'");
          $stmt_show_cancelled->bind_param("i", $_SESSION["id"]);
          $stmt_show_cancelled->execute();
          $cancelled_result = $stmt_show_cancelled->get_result();
          
          if ($cancelled_result->num_rows == 0) {
            echo '<p class="text-center text-muted fw-semibold mt-4">No transaction available.</p>';
          }
          while ($cancelled_row = $cancelled_result->fetch_assoc()):
            $reserve_date = date("F j, Y", strtotime($cancelled_row["reserve_date"]));
        ?>
            <div class="card transaction border-0 shadow-sm rounded-4 mb-3 cursor-pointer"
              data-title="<?= $cancelled_row["title"] ?>"
              data-author="<?= $cancelled_row["author"] ?>"
              data-reservedate="<?= $cancelled_row["reserve_date"] ?>"
              data-borrowdate="<?= $cancelled_row["borrow_date"] ?>"
              data-returndate="<?= $cancelled_row["return_date"] ?>"
              data-duedate="<?= $cancelled_row["due_date"] ?>"
              data-notes="<?= $cancelled_row["notes"] ?? ""?>"
              data-fineamount="<?= $cancelled_row["fine_amount"] ?>"
              data-status="<?= $cancelled_row["status"] ?>"
            >
                <div class="card-body">
                  <div class="row justify-content-between align-items-center">
                    <div class="col-md-6">
                      <div class="d-flex justify-content-between align-items-center gap-2">
                        <h6 class="mb-0 fw-normal"> 
                          <a href="book_information.php?book_id=<?= $cancelled_row["book_id"]; ?>&source=transactions&tab=Cancelled" class="text-dark fw-semibold link-offset-1 link-underline-dark link-underline-opacity-50 link-underline-opacity-75-hover">
                            <?= $cancelled_row["title"] ?> 
                          </a>
                          by
                          <span class="fw-semibold">  <?= $cancelled_row["author"] ?></span>
                        </h6>
                      </div>
                    </div>
                    <div class="col-md-6 my-2 my-md-0 d-flex flex-column flex-lg-row justify-content-lg-end gap-lg-3">
                      <small class="text-muted text-start">
                        <span class="d-block"> 
                          Reserve Date: <span class="fw-semibold"><?= $reserve_date ?></span> 
                        </span>
                      </small>
                    </div>
                  </div>
                </div>
            </div>
        <?php
          endwhile;
        ?>
      </div>
    </div>

    <div class="tab-pane fade" id="lost" role="tabpanel">
      <h5 class="fw-semibold text-center"><i class="bi bi-question-circle-fill"></i> Lost Book(s)</h5>
      <div class="container my-4">
        <?php
          $stmt_show_lost = $conn->prepare("SELECT 
              t.*,
              t.fine_amount,
              b.id AS book_id,
              b.title,
              b.author
            FROM transactions t
            JOIN books b ON t.book_id = b.id
            WHERE t.user_id = ? AND status = 'Lost'");
          $stmt_show_lost->bind_param("i", $_SESSION["id"]);
          $stmt_show_lost->execute();
          $lost_result = $stmt_show_lost->get_result();
          
          if ($lost_result->num_rows == 0) {
            echo '<p class="text-center text-muted fw-semibold mt-4">No transaction available.</p>';
          }
          while ($lost_row = $lost_result->fetch_assoc()):
            $reserve_date = date("F j, Y", strtotime($lost_row["reserve_date"]));
            $return_date = date("F j, Y", strtotime($lost_row["return_date"]));
            $lost_id = $lost_row["id"];
        ?>
            <div class="card border-1 border-danger shadow-sm rounded-4 mb-3 cursor-pointer"
              data-title="<?= $lost_row["title"] ?>"
              data-author="<?= $lost_row["author"] ?>"
              data-reservedate="<?= $lost_row["reserve_date"] ?>"
              data-borrowdate="<?= $lost_row["borrow_date"] ?>"
              data-returndate="<?= $lost_row["return_date"] ?>"
              data-duedate="<?= $lost_row["due_date"] ?>"
              data-notes="<?= $lost_row["notes"] ?? ""?>"
              data-fineamount="<?= $lost_row["fine_amount"] ?>"
              data-status="<?= $lost_row["status"] ?>"
              onclick="window.location.href='fines.php'"
            >
                <div class="card-body">
                  <div class="row justify-content-between align-items-center">
                    <div class="col-md-4">
                      <div class="d-flex justify-content-between align-items-center gap-2">
                        <h6 class="mb-0 fw-normal"> 
                          <a href="book_information.php?book_id=<?= $lost_row["book_id"]; ?>&source=transactions&tab=lost" class="text-dark fw-semibold link-offset-1 link-underline-dark link-underline-opacity-50 link-underline-opacity-75-hover">
                            <?= $lost_row["title"] ?> 
                          </a>
                          by
                          <span class="fw-semibold">  <?= $lost_row["author"] ?></span>
                        </h6>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="d-flex justify-content-lg-end align-items-center gap-3 mt-2 mt-lg-0">
                        <small class='text-muted text-start'>
                          <span class='d-block'> 
                            Fine Amount: <span class='fw-semibold'>₱<?= $lost_row["fine_amount"] ?></span> 
                          </span>
                        </small>
                      </div>
                    </div>
                    <div class="col-md-4 my-2 my-md-0 d-flex flex-column flex-lg-row justify-content-lg-end gap-lg-3">
                      <small class="text-muted text-start">
                        <span class="d-block">
                          Reserve Date: <span class="fw-bold"><?= $reserve_date ?></span>
                        </span>
                        <span class="d-block"> 
                          Return Date: <span class="fw-semibold"><?= $return_date ?></span> 
                        </span>
                      </small>
                    </div>
                  </div>
                </div>
            </div>
        <?php
          endwhile;
        ?>
      </div>
    </div>

    <div class="tab-pane fade" id="completed" role="tabpanel">
      <h5 class="fw-semibold text-center"><i class="bi bi-check-square-fill"></i> Completed Book(s)</h5>
      <div class="container my-4">
        <?php
          $stmt_show_completed = $conn->prepare("SELECT 
              t.*,
              t.fine_amount,
              b.id AS book_id,
              b.title,
              b.author,
              f.paid_at AS paid_at
            FROM transactions t
            JOIN books b ON t.book_id = b.id
            LEFT JOIN fines f ON t.id = f.transaction_id
            WHERE t.user_id = ? AND t.status = 'Completed'");
          $stmt_show_completed->bind_param("i", $_SESSION["id"]);
          $stmt_show_completed->execute();
          $completed_result = $stmt_show_completed->get_result();
          
          if ($completed_result->num_rows == 0) {
            echo '<p class="text-center text-muted fw-semibold mt-4">No transaction available.</p>';
          }
          while ($completed_row = $completed_result->fetch_assoc()):
            $borrow_date = date("F j, Y", strtotime($completed_row["borrow_date"]));
            $paid_date = date("F j, Y", strtotime($completed_row["paid_at"]));
            $completed_id = $completed_row["id"];
        ?>
            <div class="card border-0 shadow-sm rounded-4 mb-3 cursor-pointer"
              data-title="<?= $completed_row["title"] ?>"
              data-author="<?= $completed_row["author"] ?>"
              data-reservedate="<?= $completed_row["reserve_date"] ?>"
              data-borrowdate="<?= $completed_row["borrow_date"] ?>"
              data-returndate="<?= $completed_row["return_date"] ?>"
              data-duedate="<?= $completed_row["due_date"] ?>"
              data-returneddate="<?= $completed_row["returned_date"] ?>"
              data-notes="<?= $completed_row["notes"] ?? ""?>"
              data-fineamount="<?= $completed_row["fine_amount"] ?>"
              data-status="<?= $completed_row["status"] ?>"
              onclick="window.location.href='fines.php?tab=paid'"
            >
                <div class="card-body">
                  <div class="row justify-content-between align-items-center">
                    <div class="col-md-4">
                      <div class="d-flex justify-content-between align-items-center gap-2">
                        <h6 class="mb-0 fw-normal"> 
                          <a href="book_information.php?book_id=<?= $completed_row["book_id"]; ?>&source=transactions&tab=completed" class="text-dark fw-semibold link-offset-1 link-underline-dark link-underline-opacity-50 link-underline-opacity-75-hover">
                            <?= $completed_row["title"] ?> 
                          </a>
                          by
                          <span class="fw-semibold">  <?= $completed_row["author"] ?></span>
                        </h6>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="d-flex justify-content-lg-end align-items-center gap-3 mt-2 mt-lg-0">
                        <small class='text-muted text-start'>
                          <span class='<?= ($completed_row["notes"] == "") ? "d-none" : "d-block" ?>'> 
                            Notes: <span class='fw-semibold'><?= $completed_row["notes"] ?></span> 
                          </span>
                          <span class='d-block'> 
                            Fine Amount: <span class='fw-semibold'>₱<?= $completed_row["fine_amount"] ?></span> 
                          </span>
                        </small>
                      </div>
                    </div>
                    <div class="col-md-4 my-2 my-md-0 d-flex flex-column flex-lg-row justify-content-lg-end gap-lg-3">
                      <small class="text-muted text-start">
                        <span class="d-block">
                          Borrow Date: <span class="fw-bold"><?= $borrow_date ?></span>
                        </span>
                        <span class="d-block"> 
                          Paid On: <span class="fw-semibold"><?= $paid_date ?></span> 
                        </span>
                      </small>
                    </div>
                  </div>
                </div>
            </div>
        <?php
          endwhile;
        ?>
      </div>
    </div>

    <div class="tab-pane fade" id="history" role="tabpanel">
      <h5 class="fw-semibold text-center d-flex justify-content-center align-items-center gap-3">
        <i class="bi bi-hourglass-split"></i> History
        <div class="d-flex align-items-center gap-1">
          <label for="filter_status" class="form-label m-0 p-0"> <i class="bi bi-filter fs-4 fw-bold text-muted"></i> </label>
          <select id="filter_status" class="form-select w-auto">
            <option value="All">All</option>
            <option value="Reserved">Reserved</option>
            <option value="Borrowed">Borrowed</option>
            <option value="Returned">Returned</option>
            <option value="Overdue">Overdue</option>
            <option value="Cancelled">Cancelled</option>
            <option value="Lost">Lost</option>
          </select>
        </div>
      </h5>
      <div class="container my-4">
        <?php
          $stmt_show_history = $conn->prepare("SELECT 
              t.*,
              t.fine_amount,
              b.id AS book_id,
              b.title,
              b.author
            FROM transactions t
            JOIN books b ON t.book_id = b.id
            LEFT JOIN fines f ON t.id = f.transaction_id
            WHERE t.user_id = ?
            ORDER BY id DESC");
          $stmt_show_history->bind_param("i", $_SESSION["id"]);
          $stmt_show_history->execute();
          $history_result = $stmt_show_history->get_result();
          
          if ($history_result->num_rows == 0) {
            echo '<p class="text-center text-muted fw-semibold mt-4">No transaction available.</p>';
          }
          while ($history_row = $history_result->fetch_assoc()):
            $borrow_date = date("F j, Y", strtotime($history_row["borrow_date"]));
            $return_date = date("F j, Y", strtotime($history_row["return_date"]));
            $due_date = date("F j, Y", strtotime($history_row["due_date"]));
            $returned_date = !empty($history_row["returned_date"]) 
              ? date("F j, Y", strtotime($history_row["returned_date"])) 
              : null;

            $cancellation_date = !empty($history_row["cancellation_date"]) 
              ? date("F j, Y", strtotime($history_row["cancellation_date"])) 
              : null;

            $completion_date = !empty($history_row["completion_date"]) 
              ? date("F j, Y", strtotime($history_row["completion_date"])) 
              : null;

            $paid_date = !empty($history_row["paid_at"]) 
              ? date("F j, Y", strtotime($history_row["paid_at"])) 
              : null;
        ?>
            <div class="card history transaction border-0 shadow-sm rounded-4 mb-3 cursor-pointer"
              data-title="<?= $history_row["title"] ?>"
              data-author="<?= $history_row["author"] ?>"
              data-reservedate="<?= $history_row["reserve_date"] ?>"
              data-borrowdate="<?= $history_row["borrow_date"] ?>"
              data-returndate="<?= $history_row["return_date"] ?>"
              data-duedate="<?= $history_row["due_date"] ?>"
              data-notes="<?= $history_row["notes"] ?? ""?>"
              data-fineamount="<?= $history_row["fine_amount"] ?>"
              data-status="<?= $history_row["status"] ?>"
            >
                <div class="card-body">
                  <div class="row justify-content-between align-items-center">
                    <div class="col-md-4">
                      <div class="d-flex flex-row justify-content-between align-items-center gap-2">
                        <h6 class="mb-0 fw-normal"> 
                          <a href="book_information.php?book_id=<?= $history_row["book_id"]; ?>&source=transactions&tab=history" class="text-dark fw-semibold link-offset-1 link-underline-dark link-underline-opacity-50 link-underline-opacity-75-hover">
                            <?= $history_row["title"] ?> 
                          </a>
                          by
                          <span class="fw-semibold">  <?= $history_row["author"] ?></span>
                        </h6>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <?php
                        switch ($history_row["status"]) {
                          case "Reserved": $bg_text_color = "bg-warning text-dark"; $icon = "bookmark"; break;
                          case "Borrowed": $bg_text_color = "bg-primary text-light"; $icon = "journal-bookmark"; break;
                          case "Overdue": $bg_text_color = "bg-danger text-white"; $icon = "exclamation-triangle"; break;
                          case "Returned": $bg_text_color = "bg-success text-white"; $icon = "check-circle"; break;
                          case "Cancelled": $bg_text_color = "bg-secondary text-white"; $icon = "x-circle"; break;
                          case "Lost": $bg_text_color = "bg-danger text-white"; $icon = "question-circle"; break;
                        }
                        $fine_amount = $history_row["fine_amount"];
                      ?>
                      <div class="d-flex align-items-center gap-3 mt-2 mt-lg-0">
                        <?php
                          if ($history_row["status"] == "Overdue" || $history_row["status"] == "Lost") {
                            echo "
                              <small class='text-muted text-start'>
                                <span class='d-block'> 
                                  Fine Amount: <span class='fw-semibold'>₱$fine_amount</span> 
                                </span>
                              </small>";
                          }
                        ?>
                        <span class="badge <?= $bg_text_color ?> align-middle ms-auto px-3 py-2">
                          <i class="bi bi-<?= $icon; ?>"></i> 
                          <?= $history_row["status"] ?> 
                        </span>
                      </div>
                    </div>
                    <div class="col-md-4 my-2 my-md-0 d-flex flex-column flex-lg-row justify-content-lg-end gap-lg-3">
                      <small class="text-muted text-start">
                        <span class="d-block"> 
                          Borrow Date: <span class="fw-semibold"><?= $borrow_date ?></span> 
                        </span>
                        <span class="d-block"> 
                          Return Date: <span class="fw-semibold"><?= $return_date ?></span> 
                        </span>
                      </small>
                    </div>
                  </div>
                </div>
            </div>
        <?php
          endwhile;
        ?>
      </div>
    </div>
  </section>


  <div class="modal fade p-4" id="show_transaction_information" tabindex="-1">  
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content rounded-4 shadow">
        <div class="modal-header border-0">
          <h5 class="modal-title w-100 text-center fw-bold">TRANSACTION INFORMATION</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>     
        <div class="modal-body text-center">
          <table id="transaction-information-table" class="table table-borderless table-hover text-start">
            <tr>
              <th>Book Title:</th>
              <td id="book_title"></td>
            </tr>
            <tr>
              <th>Book Author:</th>
              <td id="book_author"></td>
            </tr>
            <tr>
              <th>Reserve Date:</th>
              <td id="reserve_date"></td>
            </tr>
            <tr>
              <th>Borrow Date:</th>
              <td id="borrow_date"></td>
            </tr>
            <tr>
              <th>Return Date:</th>
              <td id="return_date"></td>
            </tr>
            <tr>
              <th>Due Date:</th>
              <td id="due_date"></td>
            </tr>
            <tr>
              <th>Notes:</th>
              <td id="notes"></td>
            </tr>
            <tr>
              <th>Fine Amount:</th>
              <td id="fine_amount"></td>
            </tr>
            <tr>
              <th>Status:</th>
              <td id="status"></td>
            </tr>
          </table>
        </div>
      </div>  
    </div>
  </div>

  <div class="modal fade p-4" id="edit_reservation" tabindex="-1">  
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content rounded-4 shadow">
        <div class="modal-header border-0">
          <h5 class="modal-title w-100 text-center fw-bold">EDIT RESERVATION</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>     
        <div class="modal-body text-center">
          <form method="POST" novalidate>
            <input type="hidden" id="edit_id" name="edit_id">
            <input type="hidden" id="old_book_id" name="old_book_id">

            <p id="reserve_message"></p>
            <div class="row mb-2 gx-3 gy-2">
              <div class="col-sm form-floating">
                <select class="form-select" id="edit_book_id" name="edit_book_id" required>
                  <option value="" selected disabled>--Select--</option>
                  <?php 
                    $book_list = $conn->query("SELECT id, title, author, available_copies FROM books WHERE availability = 'Available'");

                    while ($row = $book_list->fetch_assoc()) {
                      $book_id = $row["id"];
                      $book_title = $row["title"];
                      $book_author = $row["author"];
                      $copies = $row["available_copies"];
                      $disabled = ($copies <= 0) ? "disabled" : "";

                      echo "<option value='$book_id' data-copies='$copies' $disabled $disabled>$book_title by $book_author</option>";
                    }
                  ?>           
                </select>
                <label for="edit_book_id" class="form-label">Select book</label>
              </div>
            </div>
            <div class="row mb-2 gx-3 gy-2">
              <div class="col-sm form-floating">
                <input type="date" class="form-control" id="edit_borrow_date" name="edit_borrow_date" min="<?= date('Y-m-d'); ?>" required>
                <label for="edit_borrow_date" class="form-label">Select borrow date</label>
              </div>
              <div class="col-sm form-floating">
                <input type="date" class="form-control" id="edit_return_date" name="edit_return_date" min="<?= date('Y-m-d'); ?>" disabled required>
                <label for="edit_return_date" class="form-label">Select return date</label>
              </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-center">
              <button type="button" class="btn btn-md btn-danger rounded-3 px-3 me-3" data-bs-dismiss="modal">Cancel</button>
              <input type="submit" name="edit_reservation" value="Update" class="btn btn-success">
            </div>
          </form>
        </div>      
      </div>
    </div>
  </div>

  <div class="modal fade p-4" id="cancel_reservation" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content rounded-4 shadow">
        <div class="modal-header border-0">
          <h5 class="modal-title w-100 text-center fw-bold">CANCEL RESERVATION?</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>     
        <div class="modal-body text-center">
          <i class="fa-solid fa-triangle-exclamation fa-4x text-danger mb-3"></i>
          <p class="mb-0 px-2">Are you sure you want cancel this reservation? This action cannot be undone.</p>
        </div>      
        <div class="modal-footer border-0 d-flex justify-content-center">
          <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
          <form method="POST">
            <input type="hidden" name="cancel_id" id="cancel_id">
            <input type="hidden" name="cancel_book_id" id="cancel_book_id">
            <input type="submit" name="cancel_reservation" value="Yes, Cancel" class="btn bg-danger text-light rounded-3 px-4">
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    const urlParams = new URLSearchParams(window.location.search);
    const tabFromUrl = urlParams.get("tab");

    if (tabFromUrl) {
      const triggerEl = document.querySelector(`#transactions_tab button[data-bs-target="#${tabFromUrl}"]`);
      if (triggerEl) {
        const tab = new bootstrap.Tab(triggerEl);
        tab.show();
      }
    }

    document.querySelectorAll('#transactions_tab button[data-bs-toggle="tab"]').forEach(button => {
      button.addEventListener('shown.bs.tab', (event) => {
        const targetId = event.target.getAttribute('data-bs-target').substring(1);
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('tab', targetId);
        window.history.replaceState({}, '', newUrl);
      });
    });


    const searchInput = document.getElementById("search_input");
    const filterStatus = document.getElementById("filter_status");
    const cards = document.querySelectorAll(".card.history");

    function filterCards() {
      const query = searchInput.value.toLowerCase();
      const statusFilter = filterStatus.value.toLowerCase();

      cards.forEach(card => {
        const dataText = [
          card.dataset.title,
          card.dataset.author,
          card.dataset.description,
          card.dataset.reservedate,
          card.dataset.borrowdate,
          card.dataset.returndate,
          card.dataset.duedate,
          card.dataset.status
        ].join(" ").toLowerCase();

        const status = card.dataset.status.toLowerCase();

        const matchesSearch = query === "" || dataText.includes(query);
        const matchesStatus = statusFilter === "all" || status === statusFilter;

        card.classList.toggle("d-none", !(matchesSearch && matchesStatus));
      });
    }

    searchInput.addEventListener("input", filterCards);
    filterStatus.addEventListener("change", filterCards);


    document.querySelectorAll(".transaction").forEach(transaction => {
      transaction.addEventListener("click", () => {
        function formatDate(dateValue) {
          const date = dateValue.split(" ")[0];

          const dateObj = new Date(date);
          const month = dateObj.toLocaleString('en-US', { month: 'long' });
          const day = String(dateObj.getDate()).padStart(2, '0');
          const year = dateObj.getFullYear();

          return `${month} ${day}, ${year}`;
        }

        document.getElementById("book_title").innerText = transaction.dataset.title;
        document.getElementById("book_author").innerText = transaction.dataset.author;
        document.getElementById("reserve_date").innerText = formatDate(transaction.dataset.reservedate);
        document.getElementById("borrow_date").innerText = formatDate(transaction.dataset.borrowdate);
        document.getElementById("return_date").innerText = formatDate(transaction.dataset.returndate);
        document.getElementById("due_date").innerText = formatDate(transaction.dataset.duedate);

        document.getElementById("notes").innerText = (transaction.dataset.notes) ? transaction.dataset.notes : "N/A";
        document.getElementById("fine_amount").innerText = (transaction.dataset.fineamount) ? transaction.dataset.fineamount : "";

        switch (transaction.dataset.status) {
          case "Reserved": bg_text_color = "bg-warning text-dark"; icon = "bookmark"; break;
          case "Borrowed": bg_text_color = "bg-primary text-white"; icon = "journal-bookmark"; break;
          case "Overdue": bg_text_color = "bg-danger text-white"; icon = "exclamation-triangle"; break;
          case "Returned": bg_text_color = "bg-success text-white"; icon = "check-circle"; break;
          case "Cancelled": bg_text_color = "bg-secondary text-white"; icon = "x-circle"; break;
          case "Lost": bg_text_color = "bg-danger text-white"; icon = "question-circle"; break;
        }

        document.getElementById("status").innerHTML = 
          `<span class="badge ${bg_text_color} align-middle px-3 py-2">
            <i class="bi bi-${icon}"></i> 
            ${transaction.dataset.status} 
          </span>`

        const modal = new bootstrap.Modal(document.getElementById("show_transaction_information"));
        modal.show();
      })
    })
    
    document.querySelectorAll(".edit-button").forEach(btn => {
      btn.addEventListener("click", () => {
        const oldBookId = btn.dataset.bookid;
        const select = document.getElementById("edit_book_id");

        document.getElementById("edit_id").value = btn.dataset.id;
        document.getElementById("old_book_id").value = btn.dataset.bookid;
        document.getElementById("edit_book_id").value = btn.dataset.bookid;

        Array.from(select.options).forEach(opt => {
            const copies = parseInt(opt.dataset.copies) || 0;
            opt.disabled = copies <= 0;
        });

        const currentOption = select.querySelector(`option[value='${oldBookId}']`);
        if (currentOption) currentOption.disabled = false;

        select.value = oldBookId;

        const borrowInput = document.getElementById("edit_borrow_date");
        borrowInput.value = btn.dataset.borrowdate.split(" ")[0];;

        const returnInput = document.getElementById("edit_return_date");
        returnInput.value = btn.dataset.returndate.split(" ")[0];;
        returnInput.disabled = false;

        const modal = new bootstrap.Modal(document.getElementById("edit_reservation"));
        modal.show();
      });
    });

    const editBorrowDate = document.getElementById("edit_borrow_date");
    const editReturnDate = document.getElementById("edit_return_date");

    editBorrowDate.addEventListener("change", () => {
      if (editBorrowDate.value) {
        editReturnDate.disabled = false;

        editReturnDate.min = editBorrowDate.value;

        const borrow = new Date(editBorrowDate.value);
        const maxReturn = new Date(borrow);
        maxReturn.setMonth(maxReturn.getMonth() + 1);

        const yyyy = maxReturn.getFullYear();
        const mm = String(maxReturn.getMonth() + 1).padStart(2, '0');
        const dd = String(maxReturn.getDate()).padStart(2, '0');
        editReturnDate.max = `${yyyy}-${mm}-${dd}`;
      } else {
        editReturnDate.disabled = true;
        editReturnDate.value = '';
      }
    });

    editReturnDate.addEventListener("change", () => {
      if (editReturnDate.value) {
        const returnValue = new Date(editReturnDate.value);

        const ry = returnValue.getFullYear();
        const rm = returnValue.toLocaleString('en-US', { month: 'long' });
        const rd = String(returnValue.getDate()).padStart(2, '0');
        
        const due = new Date(returnValue);
        due.setDate(due.getDate() + 7); 

        const dy = due.getFullYear();
        const dm = due.toLocaleString('en-US', { month: 'long' });
        const dd = String(due.getDate()).padStart(2, '0');
        document.getElementById("reserve_message").innerHTML = `Please return the book on <span class='fw-bold'>${rm} ${rd}, ${ry}</span> or not later than <span class='fw-bold'>${dm} ${dd}, ${dy}</span> to avoid fines.`;
      } 
    });

    document.querySelectorAll(".cancel-button").forEach(btn => {
      btn.addEventListener("click", ()=> {
        document.getElementById("cancel_id").value = btn.dataset.id;
        document.getElementById("cancel_book_id").value = btn.dataset.bookid;
        
        const modal = new bootstrap.Modal(document.getElementById("cancel_reservation"));
        modal.show();
      });
    });
  </script>
</main>

<?php
  showFooter();
?>