<?php
  include("config.php");

  header("Content-Type: application/json");
  ob_start();

  $sql = "SELECT id, due_date FROM transactions WHERE status = 'Borrowed'";
  $result = $conn->query($sql);

  while ($row = $result->fetch_assoc()) {
    $transac_id = $row["id"];
    $due_date = $row["due_date"];

    $days_late = (strtotime(date("Y-m-d")) - strtotime($due_date)) / 86400;

    if ($days_late > 0) {
      $fine = $days_late * 10;

      $stmt = $conn->prepare("UPDATE transactions SET status = 'Overdue', fine_amount = ? WHERE id = ?");
      $stmt->bind_param("ii", $fine, $transac_id);
      $stmt->execute();

      $stmt2 = $conn->prepare("INSERT INTO fines (transaction_id, amount, reason) VALUES (?, ?, 'Overdue')");
      $stmt2->bind_param("ii", $transac_id, $fine);
      $stmt2->execute();
    }
  }
  
  ob_end_clean();

  echo json_encode(["success" => true]);
?>
