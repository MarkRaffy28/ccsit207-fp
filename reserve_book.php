<?php
  session_start();

  if(!isset($_SESSION["id"])) {
    header ("Location: index.php");
    exit;
  } elseif($_SESSION["id"] == "0") {
    header ("Location: admin_dashboard.php");
    exit;
  }

  include("config.php");
  include("components.php");

  if (isset($_POST["reserve_book"])) {
    $user_id = $_SESSION["id"];
    $book_id = test_input($_POST["book_id"]);
    $borrow_date = test_input($_POST["borrow_date"]);
    $return_date = test_input($_POST["return_date"]);
    $due_date = date('Y-m-d', strtotime($return_date . ' +7 days'));

    $stmt_check_overdue = $conn->prepare("SELECT id FROM transactions WHERE user_id = ? AND status = 'Overdue'");
    $stmt_check_overdue->bind_param("i", $user_id);
    $stmt_check_overdue->execute();
    $stmt_check_overdue->store_result();

    if ($stmt_check_overdue->num_rows > 0) {
      $_SESSION["msg"] = ["danger", "You cannot reserve a book because you have overdue books. Please return them first."];
      header ("Location: " . $_SERVER["PHP_SELF"]);
      exit;
    }

    $stmt_check_reserved_book = $conn->prepare("SELECT id FROM transactions WHERE user_id = ? AND book_id = ? AND status = 'Reserved'");
    $stmt_check_reserved_book->bind_param("ii", $user_id, $book_id);
    $stmt_check_reserved_book->execute();
    $stmt_check_reserved_book->store_result();
    
    if ($stmt_check_reserved_book->num_rows > 0) {
      $_SESSION["msg"] = ["danger", "You already reserved this book. Please complete or cancel it before reserving again."];
      header ("Location: " . $_SERVER["PHP_SELF"]);
      exit;
    }
  
    $stmt_check_borrowed_book = $conn->prepare("SELECT id FROM transactions WHERE user_id = ? AND book_id = ? AND status = 'Borrowed'");
    $stmt_check_borrowed_book->bind_param("ii", $user_id, $book_id);
    $stmt_check_borrowed_book->execute();
    $stmt_check_borrowed_book->store_result();
    
    if ($stmt_check_borrowed_book->num_rows > 0) {
      $_SESSION["msg"] = ["danger", "You already borrowed this book. Please complete or cancel it before reserving again."];
      header ("Location: " . $_SERVER["PHP_SELF"]);
      exit;
    } 

    $stmt_reserve_book = $conn->prepare("INSERT INTO transactions(user_id, book_id, borrow_date, return_date, due_date, status) VALUES(?, ?, ?, ?, ?, 'Reserved')");
    $stmt_reserve_book->bind_param("iisss", $user_id, $book_id, $borrow_date, $return_date, $due_date);
    
    if (!$stmt_reserve_book->execute()) {
      $_SESSION["msg"] = ["danger", "Reservation error. Please try again later."];
      exit;
    }

    $stmt_dec_book_copy = $conn->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
    $stmt_dec_book_copy->bind_param("i", $book_id);
    $stmt_dec_book_copy->execute();

    $_SESSION["msg"] = ["success", "Book reserved successfully"];
    header ("Location: " . $_SERVER["PHP_SELF"]);
    exit;
  }

  showHeader("Reserve Book");
?>

<main class="m-4">
  <?= showAlert(); ?>
  <section class="my-3">
    <h3 class="fw-semibold"><i class="bi bi-bookmark"></i> Reserve Book</h3>
  </section>

  <section class="mt-4 px-lg-6">
    <form method="POST" class="mx-md-5 my-md-4 px-md-5" novalidate>
      <p id="reserve_message"></p>
      <div class="row mb-2 gx-3 gy-2">
        <div class="col-sm form-floating">
          <select class="form-select" id="book_id" name="book_id" required>
            <option value="" selected disabled>--Select--</option>
            <?php 
              $book_list = $conn->query("SELECT id, title, author FROM books WHERE availability = 'Available' AND available_copies > 0");

              while ($row = $book_list->fetch_assoc()) {
                $book_id = $row["id"];
                $book_title = $row["title"];
                $book_author = $row["author"];
                $selected = (isset($_GET["book_id"]) && $_GET["book_id"] == $book_id) ? "selected" : "";

                echo "<option value=$book_id $selected>$book_title by $book_author</option>";
              }
            ?>           
          </select>
          <label for="book_id" class="form-label">Select book</label>
        </div>
      </div>
      <div class="row mb-2 gx-3 gy-2">
        <div class="col-sm form-floating">
          <input type="date" class="form-control" id="borrow_date" name="borrow_date" min="<?= date('Y-m-d'); ?>" required>
          <label for="borrow_date" class="form-label">Select borrow date</label>
        </div>
        <div class="col-sm form-floating">
          <input type="date" class="form-control" id="return_date" name="return_date" min="<?= date('Y-m-d'); ?>" disabled required>
          <label for="return_date" class="form-label">Select return date</label>
        </div>
        <div class="mt-3 mx-2 form-check">
          <input type="checkbox" class="form-check-input" id="rules_and_guidelines_check" required>
          <label class="form-check-label" for="rules_and_guidelines_check">
            I have read and agreed to <a type="button" data-bs-toggle="modal" data-bs-target="#rules_and_guidelines" class="link-opacity-50-hover">Library Borrowing Rules and Guidelines</a>
          </label>
        </div>
      </div>
      <div class="mt-4 d-flex justify-content-center">
        <input type="submit" name="reserve_book" class="btn btn-success">
      </div>
    </form>
  </section>


  <div class="modal fade" id="rules_and_guidelines" tabindex="-1">
    <div class="modal-lg modal-dialog modal-dialog-centered modal-dialog-scrollable p-4">
      <div class="modal-content">
        <div class="modal-header pb-0">          
          <h4 class="modal-title w-100 text-center fw-bold m-0 p-0">Library Borrowing Rules and Guidelines</h4>  
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>      
        <div class="modal-body">
          <h5>Who Can Borrow:</h5>
          <p class="ps-3">Only students, teachers, and staff are allowed to borrow books from the library.</p>
          <h5>Borrowing Period:</h5>
          <p class="ps-3">Each book can be borrowed for 1 month from the date of borrowing.</p>
          <h5>Pick-up:</h5>
          <ul>
            <li>Books must be picked up at the library to be considered fully borrowed. Online reservations alone do not complete the borrowing process.</li>
            <li>Books must be picked up on the specified borrowed date. If the borrower fails to pick up the book on the chosen borrow date, the reservation will be canceled.</li>
          </ul>

          <h5>Overdue and Fines:</h5>
          <ul>
            <li>If a book is returned late, 7 days after the due date, a fine of <strong>₱10</strong> a day shall be charged for every overdue book returned by a borrower.</li>
            <li>The book is considered overdue until it is returned.</li>
            <li>A borrower with an overdue book cannot borrow more until the overdue book is returned and the corresponding fine has been settled. </li>
          </ul>
          <h5>Lost or Damaged Books:</h5>
          <p class="ps-3">A lost or damaged books borrowed from the library shall be paid by the borrower at the current prevailing price or may be replaced upon mutual agreement of the borrower and the librarian.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    const borrowDate = document.getElementById("borrow_date");
    const returnDate = document.getElementById("return_date");

    borrowDate.addEventListener("change", () => {
      if (borrowDate.value) {
        returnDate.disabled = false;

        returnDate.min = borrowDate.value;

        const borrow = new Date(borrowDate.value);
        const maxReturn = new Date(borrow);
        maxReturn.setMonth(maxReturn.getMonth() + 1);

        const yyyy = maxReturn.getFullYear();
        const mm = String(maxReturn.getMonth() + 1).padStart(2, '0');
        const dd = String(maxReturn.getDate()).padStart(2, '0');
        returnDate.max = `${yyyy}-${mm}-${dd}`;
      } else {
        returnDate.disabled = true;
        returnDate.value = '';
      }
    });

  returnDate.addEventListener("change", () => {
    if (returnDate.value) {
      const returnValue = new Date(returnDate.value);

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
  </script>
</main>

<?php
  showFooter();
?>