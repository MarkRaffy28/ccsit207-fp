<?php
  session_start();

  include("config.php");
  include("components.php");

  if (isset($_POST['like_book_id']) && isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
    $book_id = test_input($_POST['book_id']);

    $stmt = $conn->prepare("SELECT id FROM liked_books WHERE user_id = ? AND book_id = ?");
    $stmt->bind_param("ii", $user_id, $book_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $stmt = $conn->prepare("DELETE FROM liked_books WHERE user_id = ? AND book_id = ?");
      $stmt->bind_param("ii", $user_id, $book_id);
      $stmt->execute();
    } else {
      $stmt = $conn->prepare("INSERT INTO liked_books(user_id, book_id) VALUES (?, ?)");
      $stmt->bind_param("ii", $user_id, $book_id);
      $stmt->execute();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
  }

  if (isset($_POST['notify_book_id']) && isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
    $book_id = test_input($_POST['notify_book_id']);

    $stmt = $conn->prepare("SELECT id FROM book_notifications WHERE user_id = ? AND book_id = ?");
    $stmt->bind_param("ii", $user_id, $book_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $stmt = $conn->prepare("DELETE FROM book_notifications WHERE user_id = ? AND book_id = ?");
      $stmt->bind_param("ii", $user_id, $book_id);
      $stmt->execute();

      $_SESSION["msg"] = ["success", "You will not be notified when the book is available!"];
      header("Location: " . $_SERVER['PHP_SELF']);
      exit;
    } else {
      $stmt = $conn->prepare("INSERT INTO book_notifications(user_id, book_id) VALUES (?, ?)");
      $stmt->bind_param("ii", $user_id, $book_id);
      $stmt->execute();
      
      $_SESSION["msg"] = ["success", "You will be notified when the book is available!"];
      header("Location: " . $_SERVER['PHP_SELF']);
      exit;
    }
  }


  showHeader("Home");
?>

<main class="m-3">
  <?= showAlert(); ?>
  <section>
    <div class="row d-flex justify-content-between align-items-center gap-3">
      <h3 class="col-12 col-lg-4 fw-semibold">List of <?= (isset($_GET["availability"]) && $_GET["availability"]=="unavailable") ? "Unavailable" : "Available"; ?> Books</h3>
      <div class="col-12 col-lg-6 d-flex align-items-center gap-3">
        <form method="GET" action="" class="ms-lg-auto">
          <div class="d-flex align-items-center gap-1">
            <label for="filter" class="form-label m-0 p-0"> <i class="bi bi-filter fs-4 fw-bold text-muted"></i> </label>
            <select name="filter" id="filter" class="form-select w-auto">
              <option value="default">Default</option>
              <option value="newest">Newest</option>
              <option value="oldest">Oldest</option>
              <option value="favorites">Favorites</option>
              <option value="most_popular">Most Popular</option>
              <option value="least_popular">Least Popular</option>
              <option value="a_z">A → Z</option>
              <option value="z_a">Z → A</option>
              <option value="today">Today</option>
              <option value="this_week">This Week</option>
              <option value="this_month">This Month</option>
              <option value="this_year">This Year</option>
              <option value="most_available_copies">Most Available Copies</option>
              <option value="least_available_copies">Least Available Copies</option>
            </select>
          </div>
        </form>
        <div class="position-relative search-container">
          <input type="text" id="search_input" class="form-control ps-5" placeholder="Search...">
          <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted">
            <i class="bi bi-search"></i>
          </span>
        </div>
      </div>
    </div>
    <div class="d-inline-block bg-secondary mt-3 mt-lg-0 px-2 py-1 rounded">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" role="switch" id="show_unavailable_books" <?= (isset($_GET["availability"]) && $_GET["availability"]=="unavailable") ? "checked" : ""; ?>>
        <label class="form-check-label text-white" for="show_unavailable_books">Show Unavailable Books</label>
      </div>
    </div>
  </section>
  <section class="mx-2 mx-lg-2 mt-4">
    <div class="books">
      <?php
        $filter = $_GET['filter'] ?? 'default';
        $availability = (isset($_GET['availability']) && $_GET["availability"]=="unavailable") ? "=0" : ">0";
        $user_id = (isset($_SESSION["id"])) ? $_SESSION["id"] : -1;

        $sql = "SELECT * FROM books WHERE available_copies $availability";

        switch ($filter) {
          case 'newest': $sql .= " ORDER BY created_at DESC"; break;
          case 'oldest': $sql .= " ORDER BY created_at ASC"; break;
          case 'most_popular': $sql = "SELECT b.*, COUNT(t.id) AS borrow_count FROM books b LEFT JOIN transactions t ON b.id = t.book_id AND t.status IN ('Borrowed','Returned') WHERE b.available_copies $availability GROUP BY b.id ORDER BY borrow_count DESC"; break;
          case 'least_popular': $sql = "SELECT b.*, COUNT(t.id) AS borrow_count FROM books b LEFT JOIN transactions t ON b.id = t.book_id AND t.status IN ('Borrowed','Returned') WHERE b.available_copies $availability GROUP BY b.id ORDER BY borrow_count ASC"; break;
          case 'favorites': $sql = "SELECT b.* FROM books b INNER JOIN liked_books lb ON b.id = lb.book_id WHERE lb.user_id = ? AND b.available_copies $availability"; break;
          case 'a_z': $sql .= " ORDER BY title ASC"; break;
          case 'z_a': $sql .= " ORDER BY title DESC"; break;
          case 'today': $sql .= " AND DATE(created_at) = CURDATE()"; break;
          case 'this_week': $sql .= " AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)"; break;
          case 'this_month': $sql .= " AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"; break;
          case 'this_year': $sql .= " AND YEAR(created_at) = YEAR(CURDATE())"; break;
          case 'most_available_copies': $sql .= " ORDER BY available_copies DESC"; break;
          case 'least_available_copies': $sql .= " ORDER BY available_copies ASC"; break;
          default: break;
        }

        $books = $conn->prepare($sql);
        if ($filter === 'favorites') {
          $books->bind_param("i", $user_id);
        }
        $books->execute();
        $result = $books->get_result();

        while ($row = $result->fetch_assoc()):
      ?>
        <div 
          class="book d-flex flex-column align-items-center"
          data-title="<?= $row["title"]; ?>"
          data-description="<?= $row["description"]; ?>"
          data-author="<?= $row["author"]; ?>"
          data-publisher="<?= $row["publisher"]; ?>"
          data-publicationyear="<?= $row["publication_year"]; ?>"
          data-isbn="<?= $row["isbn"]; ?>"
          data-genre="<?= $row["genre"]; ?>"
          data-language="<?= $row["language"]; ?>"
          data-availablecopies="<?= $row["available_copies"]; ?>"
          data-image="<?= !empty($row['image']) ? 'data:image/jpeg;base64,' . base64_encode($row['image']) : ''; ?>"
        >
          <div class="book-image position-relative">
            <?php if (!empty($row["image"])): ?>
              <img src="data:image/jpeg;base64,<?= base64_encode($row["image"]); ?>" class="rounded">
              <form method="POST">
                <?php
                  $liked_sql = $conn->prepare("SELECT id FROM liked_books WHERE user_id = ? AND book_id = ?");
                  $liked_sql->bind_param("ii", $_SESSION["id"], $row["id"]);
                  $liked_sql->execute();
                  $liked_result = $liked_sql->get_result();
                ?>
                <input type="hidden" name="like_book_id" value="<?= $row['id']; ?>">
                <button class="btn-like" onclick="event.stopPropagation()">
                  <i class="bi <?= ($liked_result->num_rows > 0) ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                </button>
              </form>
            <?php else: ?>
              <p class="m-auto text-muted">No Image</p>
            <?php endif; ?>
          </div>
          <h6 class="book-title text-center link-primary mt-2"> <?= $row["title"]; ?> </h6>
          <div>
            <?php
              if (isset($_SESSION["id"])): 
                if ($row["available_copies"] > 0):
            ?>
                <a href="" class="btn btn-sm btn-success px-5">Borrow</a>
              <?php 
                else:
                  $notified_sql = $conn->prepare("SELECT id FROM book_notifications WHERE user_id = ? AND book_id = ?");
                  $notified_sql->bind_param("ii", $_SESSION["id"], $row["id"]);
                  $notified_sql->execute();
                  $notified_result = $notified_sql->get_result();
                  $is_notified = $notified_result->num_rows > 0;
              ?>
                <form method="POST" class="notify-form">
                  <input type="hidden" name="notify_book_id" value="<?= $row['id']; ?>">
                  <button type="submit" class="btn btn-<?= ($is_notified) ? "danger" : "success"; ?> btn-sm px-3"> <?= ($is_notified) ? "Un-notify" : "Notify"; ?> Me </button>
                </form>
            <?php 
                endif;
              else: 
            ?>
              <button class="btn btn-sm btn-success px-5" data-bs-toggle="modal" data-bs-target="#login-prompt" onclick="event.stopPropagation()">Borrow</button>
            <?php endif; ?>
          </div>
        </div>

      <?php
        endwhile;
      ?>
    </div>
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
  
  <div class="modal fade p-4" id="show_book_information" tabindex="-1">  
    <div class="modal-lg modal-dialog modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content rounded-4 shadow">
        <div class="modal-header border-0">
          <h5 class="modal-title w-100 text-center fw-bold">BOOK INFORMATION</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>     
        <div class="modal-body text-center">
          <div class="row">
            <div class="col-12 col-lg-4 my-auto text-center">
              <img id="book_image" src="" alt="Book Image" class="img-fluid rounded shadow-sm mx-auto mb-3">
              <div>
                <?php if (isset($_SESSION["id"])): ?>
                  <a href="" class="btn btn-sm btn-success px-5">Borrow</a>
                <?php else: ?>
                  <button class="btn btn-sm btn-success px-5" data-bs-toggle="modal" data-bs-target="#login-prompt">Borrow</button>
                <?php endif; ?>
              </div>
            </div>
            <div class="col-12 col-lg-8">
              <table class="book-information-table table table-borderless table-hover text-start">
                <tr>
                  <th>Title:</th>
                  <td id="title"></td>
                </tr>
                <tr>
                  <th>Description:</th>
                  <td id="description"></td>
                </tr>
                <tr>
                  <th>Author:</th>
                  <td id="author"></td>
                </tr>
                <tr>
                  <th>Publisher:</th>
                  <td id="publisher"></td>
                </tr>
                <tr>
                  <th>Publication Year:</th>
                  <td id="publication_year"></td>
                </tr>
                <tr>
                  <th>ISBN:</th>
                  <td id="isbn"></td>
                </tr>
                <tr>
                  <th>Genre:</th>
                  <td id="genre"></td>
                </tr>
                <tr>
                  <th>Language:</th>
                  <td id="language"></td>
                </tr>
                <tr>
                  <th>Available Copies:</th>
                  <td id="available_copies"></td>
                </tr>
              </table>
            </div>
          </div>
        </div>
      </div>  
    </div>
  </div>
  
  <script>
    document.getElementById("search_input").addEventListener("input", () => {
      const query = document.getElementById("search_input").value.toLowerCase();
      const books = document.querySelectorAll(".book");

      books.forEach(book => {
        const dataText = [
          book.dataset.title,
          book.dataset.description,
          book.dataset.author,
          book.dataset.publisher,
          book.dataset.publicationyear,
          book.dataset.isbn,
          book.dataset.genre,
          book.dataset.language,
          book.dataset.availablecopies
        ].join(" ").toLowerCase();

        book.classList.toggle("d-none", !(query === "" || dataText.includes(query)));
      });
    });

    const select = document.getElementById('filter');
    const currentFilter = new URLSearchParams(window.location.search).get('filter') || 'default';
    select.value = currentFilter;
    
    select.addEventListener('change', function () {
      const selected = this.value;
      const url = new URLSearchParams(window.location.search);
      url.set('filter', selected);
      window.location.search = url.toString(); 
    });

    document.getElementById("show_unavailable_books").addEventListener("change", function () {

      const availability = this.checked ? "unavailable" : "available";

      const url = new URLSearchParams(window.location.search);
      url.set('availability', availability); 
      window.location.search = url.toString();
    });

    document.querySelectorAll(".book").forEach(book => {
      book.addEventListener("click", () => {
        document.getElementById("title").innerText = book.dataset.title;
        document.getElementById("description").innerText = book.dataset.description;
        document.getElementById("author").innerText = book.dataset.author;
        document.getElementById("publisher").innerText = book.dataset.publisher;
        document.getElementById("publication_year").innerText = book.dataset.publicationyear;
        document.getElementById("isbn").innerText = book.dataset.isbn;
        document.getElementById("genre").innerText = book.dataset.genre;
        document.getElementById("language").innerText = book.dataset.language;
        document.getElementById("available_copies").innerText = book.dataset.availablecopies;

        const image = document.getElementById("book_image");
        if (book.dataset.image) {
          image.src = book.dataset.image;
          image.style.display = "block";
        } else {
          image.style.display = "none";
        }

        const modal = new bootstrap.Modal(document.getElementById("show_book_information"));
        modal.show();
      })
    })

    function animateBooks() {
      const visibleBooks = document.querySelectorAll('.book:not(.d-none)');
      visibleBooks.forEach((book, index) => {
        book.classList.remove('show');
        setTimeout(() => book.classList.add('show'), index * 100);
      });
    }

    window.addEventListener('DOMContentLoaded', animateBooks);
    document.getElementById("search_input").addEventListener("input", animateBooks);
    document.getElementById("filter").addEventListener("change", animateBooks);
  </script>
</main>

<?php
  showFooter();
?>