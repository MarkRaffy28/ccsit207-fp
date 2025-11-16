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
        user_id = ?, 
        book_id = ?, 
        borrow_date = ?, 
        return_date = ?, 
        due_date = ?
      WHERE id = ? ");
    $stmt_reserve_book->bind_param("iisssi", $user_id, $edit_book_id, $edit_borrow_date, $edit_return_date, $edit_due_date, $edit_id);
    
    if (!$stmt_reserve_book->execute()) {
      $_SESSION["msg"] = ["danger", "Update reservation error. Please try again later."];
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

    $stmt_cancel_reservation = $conn->prepare("UPDATE transactions SET status = 'Cancelled' WHERE id = ?");
    $stmt_cancel_reservation->bind_param("i", $cancel_id);
    
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

<main class="m-4">
  <?= showAlert(); ?>
  <section class="my-3">
    <h3 class="fw-semibold"><i class="bi bi-receipt"></i> Transactions</h3>
    <div class=" px-lg-6">
      <ul class="nav nav-pills mt-4 border-bottom border-primary" role="tablist">
        <li class="nav-item">
          <button class="nav-link active rounded-top-3 rounded-0 rounded-bottom-0" data-bs-toggle="tab" data-bs-target="#reserved" type="button">Reserved</button>
        </li>
        <li class="nav-item">
          <button class="nav-link rounded-top-3 rounded-0 rounded-bottom-0" data-bs-toggle="tab" data-bs-target="#request" type="button">Borrowed</button>
        </li>
        <li class="nav-item">
          <button class="nav-link rounded-top-3 rounded-0 rounded-bottom-0" data-bs-toggle="tab" data-bs-target="#upcoming" type="button">Returned</button>
        </li>
        <li class="nav-item">
          <button class="nav-link rounded-top-3 rounded-0 rounded-bottom-0" data-bs-toggle="tab" data-bs-target="#history" type="button">Overdue</button>
        </li>
        <li class="nav-item">
          <button class="nav-link rounded-top-3 rounded-0 rounded-bottom-0" data-bs-toggle="tab" data-bs-target="#history" type="button">Cancelled</button>
        </li>
        <li class="nav-item">
          <button class="nav-link rounded-top-3 rounded-0 rounded-bottom-0" data-bs-toggle="tab" data-bs-target="#history" type="button">History</button>
        </li>
      </ul>
    </div>
  </section>

  <section class="tab-content px-lg-6">
    <div class="tab-pane show fade active" id="reserved" role="tabpanel">
      <h5 class="fw-semibold text-center">Reserved Book(s)</h5>
      <div class="container my-4">
        <?php
          $stmt_show_reserved = $conn->prepare("SELECT 
              t.id,
              t.borrow_date,
              t.return_date,
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
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body">
                  <div class="row justify-content-between align-items-center">
                    <div class="col-md-5">
                      <div class="d-flex justify-content-between align-items-center gap-2">
                        <h6 class="mb-0 fw-normal"> 
                          <a href="book_information.php?book_id=<?= $reserved_row["book_id"]; ?>" class="text-dark fw-semibold link-offset-1 link-underline-dark link-underline-opacity-50 link-underline-opacity-75-hover">
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
                        >
                          <i class="bi bi-pencil me-1"></i>Edit
                        </button>
                        <button class="cancel-button btn btn-sm btn-danger" 
                          data-id="<?= $reserved_row["id"] ?>"
                          data-bookid="<?= $reserved_row["book_id"] ?>"
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
  </section>


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
                      $disabled = ($row["available_copies"] <= 0) ? "disabled" : "";

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
    const editBorrowDate = document.getElementById("edit_borrow_date");
    const editReturnDate = document.getElementById("edit_return_date");
    
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