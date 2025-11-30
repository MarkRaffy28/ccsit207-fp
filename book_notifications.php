<?php
  session_start();
  
  if(!isset($_SESSION["id"])) {
    header ("Location: index.php");
    exit;
  } elseif($_SESSION["id"] == "0") {
    header ("Location: admin_dashboard.php");
    exit;
  }
  
  include "config.php";
  include "components.php";
  
  $user_id = $_SESSION["id"];

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notif_id = $_POST['notification_id'];

    if (isset($_POST['mark_read'])) {
      $stmt = $conn->prepare("UPDATE book_notifications SET is_read = 1 WHERE id = ?");
      $stmt->bind_param("i", $notif_id);
      $stmt->execute();

      header("Location: " . $_SERVER['PHP_SELF']);
      exit;
    }

    if (isset($_POST['mark_unread'])) {
      $stmt = $conn->prepare("UPDATE book_notifications SET is_read = 0 WHERE id = ?");
      $stmt->bind_param("i", $notif_id);
      $stmt->execute();
      
      header("Location: " . $_SERVER['PHP_SELF']);
      exit;
    }

    if (isset($_POST["delete_notification"])) {
      $stmt = $conn->prepare("DELETE FROM book_notifications WHERE id = ?");
      $stmt->bind_param("i", $notif_id);
      
      if (!$stmt->execute()) {
        $_SESSION["msg"] = ["danger", "Error deleting notification. Please try again later."];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
      }

      $_SESSION["msg"] = ["success", "Notification deleted successfully."];
      header("Location: " . $_SERVER['PHP_SELF']);
      exit;
    }
  }
  
  $stmt = $conn->prepare("SELECT 
      n.id,
      b.id AS book_id,
      b.title AS book_title, 
      b.author AS book_author,
      n.is_read,
      n.created_at AS notify_date
    FROM book_notifications n 
    JOIN books b ON n.book_id = b.id 
    WHERE b.available_copies > 0 AND n.user_id = ? 
    ORDER BY n.created_at DESC");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  showHeader("Book Notifications");  
?>

<main class="p-4">
  <?= showAlert(); ?>
  <section>
    <h3 class="fw-semibold"><i class="bi bi-bell"></i> Book Notifications</h3>
  </section>
  <section class="my-3">
    <?php
      if ($result->num_rows == 0) {
        echo '<p class="text-center text-muted fw-semibold mt-4">No book notifications.</p>';
      }
      while ($row = $result->fetch_assoc()): 

      $book_title = $row["book_title"];
      $book_author = $row["book_author"];
      $notify_date = date("F j, Y", strtotime($row["notify_date"]));
    ?>
      <div class="card notification mb-3 shadow-sm <?= ($row["is_read"] == 0) ? "border-1 border-danger" : "" ?>">
        <span class="<?= ($row["is_read"] == 0) ? "position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger fs-6": "d-none"; ?>">
          <i class="bi bi-exclamation"></i>
        </span>
        <div class="card-body">
          <div class="row justify-content-between align-items-center">
            <div class="col-md-8">
              <p class="mb-0 d-inline text-dark"> The book 
                <span class="fw-semibold"><?= $book_title ?></span> by 
                <span class="fw-semibold"><?= $book_author ?></span> notified from 
                <span class="fw-semibold"><?= $notify_date ?> </span>is now available! 
              </p>
            </div>
            <div class="col-md-3 d-flex justify-content-between align-items-center flex-shrink-0 gap-2 mt-2 mt-md-0">
              <a href="reserve_book.php?book_id=<?= $row["book_id"]; ?>" class="btn btn-sm btn-success">Reserve Now</a>
              <div class="d-flex gap-3">
                <form method="POST" class="d-inline">
                  <input type="hidden" name="notification_id" value="<?= $row['id'] ?>">
                  <?php if (!$row['is_read']): ?>
                    <button type="submit" name="mark_read" class="btn btn-success btn-sm"><i class="bi bi-envelope-open"></i> Mark as Read</button>
                  <?php else: ?>
                    <button type="submit" name="mark_unread" class="btn btn-danger btn-sm"><i class="bi bi-envelope"></i> Mark as Unread</button>
                  <?php endif; ?>
                  <button type="submit" name="delete_notification" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </section>
</main>

<?php
  showFooter();
?>