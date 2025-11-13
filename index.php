    <?php
      session_start();

      include("config.php");
      include("components.php");

      if (isset($_POST['book_id']) && isset($_SESSION['id'])) {
        $user_id = $_SESSION['id'];
        $book_id = intval($_POST['book_id']);

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

      showHeader("Home");
    ?>

    <main class="m-3">
      <section>
        <div>
          <h3>List of Available Books</h3><i class="bi bi-heart"></i>
        </div>
      </section>
      <section class="mx-2 mx-lg-2 mt-4">
        <div class="books">
          <?php
            $availability = $_GET["availability"] ?? "Available";

            $books = $conn->prepare("SELECT * FROM books WHERE availability = ?");
            $books->bind_param("s", $availability);
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
              data-image="<?= !empty($row['image']) ? 'data:image/jpeg;base64,' . base64_encode($row['image']) : ''; ?>"
            >
              <div class="book-image position-relative">
                <?php if (!empty($row["image"])): ?>
                  <img src="data:image/jpeg;base64,<?= base64_encode($row["image"]); ?>" class="rounded">
                  <form method="POST">
                    <?php
                      $is_liked = $conn->prepare("SELECT id FROM liked_books WHERE user_id = ? AND book_id = ?");
                      $is_liked->bind_param("ii", $_SESSION["id"], $row["id"]);
                      $is_liked->execute();
                      $liked_result = $is_liked->get_result();
                    ?>
                    <input type="hidden" name="book_id" value="<?= $row['id']; ?>">
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
                <?php if (isset($_SESSION["id"])): ?>
                  <a href="" class="btn btn-sm btn-success px-5">Borrow</a>
                <?php else: ?>
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
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
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
                  </table>
                </div>
              </div>
            </div>
          </div>  
        </div>
      </div>
      
      <script>
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
      </script>
    </main>

    <?php
      showFooter();
    ?>