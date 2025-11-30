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

  showHeader("Fines");
?>

<main class="p-4">
  <?= showAlert(); ?>
  <section class="my-3 px-lg-6">
    <div class="row d-flex justify-content-between align-items-center gy-3">
      <h3 class="col-12 col-lg-9 fw-semibold"><i class="bi bi-cash-stack"></i> Fines</h3>
      <div class="col-12 col-lg-3">
        <div class="position-relative search-container">
          <input type="text" id="search_input" class="form-control ps-5" placeholder="Search...">
          <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted">
            <i class="bi bi-search"></i>
          </span>
        </div>
      </div>
    </div>
    <div>
      <ul class="nav nav-pills mt-4 border-bottom border-primary" id="fines_tab" role="tablist">
        <li class="nav-item">
          <button class="nav-link active rounded-top-3 rounded-0 rounded-bottom-0 position-relative" data-bs-toggle="tab" data-bs-target="#unpaid" type="button">
            Unpaid
            <?php
              $unpaid_count_result = $conn->query("SELECT COUNT(f.id) FROM fines f JOIN transactions t ON f.transaction_id = t.id WHERE t.user_id = $user_id AND f.status = 'Unpaid'");
              $unpaid_count = $unpaid_count_result->fetch_row()[0]; 
              if ($unpaid_count > 0) {
                echo "<span class='badge bg-danger rounded-pill position-absolute top-75 start-100 translate-middle fs-7'>
                      $unpaid_count
                    </span>";
              }
            ?>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link rounded-top-3 rounded-0 rounded-bottom-0" data-bs-toggle="tab" data-bs-target="#paid" type="button">Paid</button>
        </li>
      </ul>
    </div>
  </section>

  <section class="tab-content px-lg-6">
    <div class="tab-pane show fade active" id="unpaid" role="tabpanel">
      <h5 class="fw-semibold text-center pb-0"><i class="bi bi-exclamation-circle-fill"></i> Unpaid Fines</h5>
      <div class="container">
        <?php
          $stmt_unpaid = $conn->prepare("SELECT 
              f.*, 
              t.book_id, 
              t.reserve_date,
              t.borrow_date,
              t.return_date,
              t.due_date,
              t.completion_date,
              b.title, 
              b.author 
            FROM fines f 
            JOIN transactions t ON f.transaction_id = t.id 
            JOIN books b ON t.book_id = b.id 
            WHERE f.status = 'Unpaid' AND t.user_id = ?");
          $stmt_unpaid->bind_param("i", $user_id);
          $stmt_unpaid->execute();
          $unpaid_result = $stmt_unpaid->get_result();

          if ($unpaid_result->num_rows == 0) {
            echo '<p class="text-center text-muted fw-semibold mt-4">No unpaid fines.</p>';
          } else {
            echo '<p class="text-center text-muted fw-semibold mb-4">Please pay fines in order to borrow another book.</p>';
          }
          while($unpaid_row = $unpaid_result->fetch_assoc()):
            $amount = number_format($unpaid_row["amount"], 2);
        ?>
            <div class="card border-1 border-danger shadow-sm rounded-4 mb-3 cursor-pointer transaction"
              data-title="<?= $unpaid_row["title"] ?>"
              data-author="<?= $unpaid_row["author"] ?>"
              data-reservedate="<?= $unpaid_row["reserve_date"] ?>"
              data-borrowdate="<?= $unpaid_row["borrow_date"] ?>"
              data-returndate="<?= $unpaid_row["return_date"] ?>"
              data-duedate="<?= $unpaid_row["due_date"] ?>"
              data-completiondate="<?= $unpaid_row["completion_date"] ?>"
              data-amount="<?= $amount ?>"
              data-reason="<?= $unpaid_row["reason"] ?>"
              data-status="<?= $unpaid_row["status"] ?>"
            >
              <div class="card-body">
                <div class="row justify-content-between align-items-center">
                  <div class="col-md-6">
                    <h6 class="mb-0 fw-normal">
                      <span class="fw-semibold">
                        <a href="book_information.php?book_id=<?= $unpaid_row["book_id"]; ?>&source=fines&tab=unpaid" class="text-dark fw-semibold link-offset-1 link-underline-dark link-underline-opacity-50 link-underline-opacity-75-hover">
                          <?= $unpaid_row["title"] ?> 
                        </a>
                        by
                        <span class="fw-semibold"><?= $unpaid_row["author"] ?>
                      </span>
                    </h6>
                  </div>
                  <div class="col-md-6 d-flex justify-content-between align-items-center gap-3 mt-2 mt-lg-0">
                    <small class='text-muted text-start'>
                      <span class='d-block'> 
                        Reason: <span class='fw-semibold'><?= $unpaid_row["reason"] ?></span> 
                      </span>
                    </small>
                    <span class="badge bg-danger px-3 py-2">₱<?= $amount ?> Unpaid</span>
                  </div>
                </div>
              </div>
            </div>
        <?php endwhile; ?>
      </div>
    </div>

    <div class="tab-pane fade" id="paid" role="tabpanel">
      <h5 class="fw-semibold text-center"><i class="bi bi-check-circle-fill"></i> Paid Fines</h5>
      <div class="container my-4">
        <?php
          $stmt_paid = $conn->prepare("SELECT 
              f.*, 
              t.book_id, 
              t.reserve_date,
              t.borrow_date,
              t.return_date,
              t.due_date,
              t.completion_date,
              b.title, 
              b.author 
            FROM fines f 
            JOIN transactions t ON f.transaction_id = t.id 
            JOIN books b ON t.book_id = b.id 
            WHERE f.status = 'Paid' AND t.user_id = ?");
          $stmt_paid->bind_param("i", $user_id);
          $stmt_paid->execute();
          $paid_result = $stmt_paid->get_result();

          if ($paid_result->num_rows == 0) {
            echo '<p class="text-center text-muted fw-semibold mt-4">No paid fines.</p>';
          }
          while($paid_row = $paid_result->fetch_assoc()):
            $amount = number_format($paid_row["amount"], 2);
        ?>
            <div class="card border-0 shadow-sm rounded-4 mb-3 cursor-pointer transaction"
              data-title="<?= $paid_row["title"] ?>"
              data-author="<?= $paid_row["author"] ?>"
              data-reservedate="<?= $paid_row["reserve_date"] ?>"
              data-borrowdate="<?= $paid_row["borrow_date"] ?>"
              data-returndate="<?= $paid_row["return_date"] ?>"
              data-duedate="<?= $paid_row["due_date"] ?>"
              data-completiondate="<?= $paid_row["completion_date"] ?>"
              data-amount="<?= $amount ?>"
              data-reason="<?= $paid_row["reason"] ?>"
              data-status="<?= $paid_row["status"] ?>"
              data-paidat="<?= $paid_row["paid_at"] ?>"
            >
              <div class="card-body">
                <div class="row justify-content-between align-items-center">
                  <div class="col-md-6">
                    <h6 class="mb-0 fw-normal">
                      <span class="fw-semibold">
                        <a href="book_information.php?book_id=<?= $paid_row["book_id"]; ?>&source=fines&tab=paid" class="text-dark fw-semibold link-offset-1 link-underline-dark link-underline-opacity-50 link-underline-opacity-75-hover">
                          <?= $paid_row["title"] ?> 
                        </a>
                        by 
                        <span class="fw-semibold"><?= $paid_row["author"] ?>
                      </span>
                    </h6>
                  </div>
                  <div class="col-md-6 d-flex justify-content-between align-items-center gap-3 mt-2 mt-lg-0">
                    <small class='text-muted text-start'>
                      <span class='d-block'> 
                        Reason: <span class='fw-semibold'><?= $paid_row["reason"] ?></span> 
                      </span>
                      <span class='d-block'> 
                        Paid on: <span class='fw-semibold'><?= date("F j, Y", strtotime($paid_row["paid_at"])) ?></span> 
                      </span>
                    </small>
                    <span class="badge bg-success px-3 py-2">₱<?= $amount ?> Paid</span>
                  </div>
                </div>
              </div>
            </div>
        <?php endwhile; ?>
      </div>
    </div>
  </section>

  <div class="modal fade p-4" id="show_fine_information" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content rounded-4 shadow">
        <div class="modal-header border-0">
          <h5 class="modal-title w-100 text-center fw-bold">FINE INFORMATION</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <table class="table table-borderless table-hover text-start">
            <tr>
              <th>Book Title:</th>
              <td id="fine_book_title"></td>
            </tr>
            <tr>
              <th>Book Author:</th>
              <td id="fine_book_author"></td>
            </tr>
            <tr>
              <th>Reserve Date:</th>
              <td id="fine_reserve_date"></td>
            </tr>
            <tr>
              <th>Borrow Date:</th>
              <td id="fine_borrow_date"></td>
            </tr>
            <tr>
              <th>Return Date:</th>
              <td id="fine_return_date"></td>
            </tr>
            <tr>
              <th>Due Date:</th>
              <td id="fine_due_date"></td>
            </tr>
            <tr>
              <th>Completion Date:</th>
              <td id="fine_completion_date"></td>
            </tr>
            <tr>
              <th>Amount:</th>
              <td id="fine_amount"></td>
            </tr>
            <tr>
              <th>Reason:</th>
              <td id="fine_reason"></td>
            </tr>
            <tr>
              <th>Status:</th>
              <td id="fine_status"></td>
            </tr>
            <tr>
              <th>Paid On:</th>
              <td id="fine_paid_at"></td>
            </tr>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    const urlParams = new URLSearchParams(window.location.search);
    const tabFromUrl = urlParams.get("tab");

    if (tabFromUrl) {
      const triggerEl = document.querySelector(`#fines_tab button[data-bs-target="#${tabFromUrl}"]`);
      if (triggerEl) {
        const tab = new bootstrap.Tab(triggerEl);
        tab.show();
      }
    }

    document.querySelectorAll('#fines_tab button[data-bs-toggle="tab"]').forEach(button => {
      button.addEventListener('shown.bs.tab', (event) => {
        const targetId = event.target.getAttribute('data-bs-target').substring(1);
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('tab', targetId);
        window.history.replaceState({}, '', newUrl);
      });
    });


    const searchInput = document.getElementById("search_input");
    const cards = document.querySelectorAll(".card");

    function filterCards() {
      const query = searchInput.value.toLowerCase();

      cards.forEach(card => {
        const dataText = [
          card.dataset.title,
          card.dataset.author,
          card.dataset.description,
          card.dataset.reservedate,
          card.dataset.borrowdate,
          card.dataset.returndate,
          card.dataset.duedate,
          card.dataset.completiondate,
          card.dataset.status,
          card.dataset.fineamount
        ].join(" ").toLowerCase();

        const matchesSearch = query === "" || dataText.includes(query);

        card.classList.toggle("d-none", !(matchesSearch));
      });
    }
    searchInput.addEventListener("input", filterCards);

    document.querySelectorAll(".transaction").forEach(fine => {
      fine.addEventListener("click", () => {
        function formatDate(dateValue) {
          if (!dateValue) {
            return "N/A";
          }
          const date = dateValue.split(" ")[0];

          const dateObj = new Date(date);
          const month = dateObj.toLocaleString('en-US', { month: 'long' });
          const day = String(dateObj.getDate()).padStart(2, '0');
          const year = dateObj.getFullYear();

          return `${month} ${day}, ${year}`;
        }
        
        document.getElementById("fine_book_title").innerText = fine.dataset.title;
        document.getElementById("fine_book_author").innerText = fine.dataset.author;
        document.getElementById("fine_reserve_date").innerText = formatDate(fine.dataset.reservedate);
        document.getElementById("fine_borrow_date").innerText = formatDate(fine.dataset.borrowdate);
        document.getElementById("fine_return_date").innerText = formatDate(fine.dataset.returndate);
        document.getElementById("fine_due_date").innerText = formatDate(fine.dataset.duedate);
        document.getElementById("fine_completion_date").innerText = formatDate(fine.dataset.completiondate);
        document.getElementById("fine_amount").innerText = "₱" + fine.dataset.amount;
        document.getElementById("fine_reason").innerText = fine.dataset.reason;

        switch (fine.dataset.status) {
          case "Unpaid": bg_text_color = "bg-danger text-white"; icon = "exclamation-circle-fill"; break;
          case "Paid": bg_text_color = "bg-success text-white"; icon = "check-circle-fill"; break;
        }

        document.getElementById("fine_status").innerHTML = 
          `<span class="badge ${bg_text_color} align-middle px-3 py-2">
            <i class="bi bi-${icon}"></i> 
            ${fine.dataset.status} 
          </span>`

        document.getElementById("fine_paid_at").innerText = (fine.dataset.paidat) ? formatDate(fine.dataset.paidat) : "-";

        const modal = new bootstrap.Modal(document.getElementById("show_fine_information"));
        modal.show();
      });
    });
  </script>
</main>

<?php
showFooter();
?>
