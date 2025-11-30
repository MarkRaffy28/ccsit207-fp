<?php
session_start();
$conn = new mysqli("localhost", "root", "", "db_library");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// --- Handle form submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_id = $_POST['transaction_id'];

    // Shift dates if the shift button is clicked
    if (isset($_POST['shift_dates'])) {
        // Fetch current return date
        $stmt = $conn->prepare("SELECT return_date FROM transactions WHERE id = ?");
        $stmt->bind_param("i", $transaction_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $return_date = $result['return_date'] ?? date('Y-m-d H:i:s');

        // Shift dates 10 days earlier
        $new_return = date('Y-m-d H:i:s', strtotime('-10 days', strtotime($return_date)));
        $new_borrow = date('Y-m-d H:i:s', strtotime('-10 days', strtotime($new_return)));
        $new_due = date('Y-m-d H:i:s', strtotime('-3 days', strtotime($new_return)));
        $new_reserve = date('Y-m-d H:i:s', strtotime('-12 days', strtotime($new_return)));

        $stmt = $conn->prepare("
            UPDATE transactions SET 
                reserve_date = ?, borrow_date = ?, due_date = ?, return_date = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssi", $new_reserve, $new_borrow, $new_due, $new_return, $transaction_id);
        $stmt->execute();
    }

    // Handle edit form (status/fine/notes)
    if (isset($_POST['edit_transaction'])) {
        $status = $_POST['status'];
        $fine_amount = $_POST['fine_amount'] ?? 0.00;
        $notes = $_POST['notes'] ?? '';

        $stmt = $conn->prepare("
            UPDATE transactions SET status=?, fine_amount=?, notes=? WHERE id=?
        ");
        $stmt->bind_param("sdsi", $status, $fine_amount, $notes, $transaction_id);
        $stmt->execute();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Fetch transactions ---
$transactions = $conn->query("
    SELECT t.*, b.title AS book_title
    FROM transactions t
    JOIN books b ON t.book_id = b.id
    ORDER BY t.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Transactions</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
<h3 class="mb-4">Admin Transaction Management</h3>
<table class="table table-bordered table-hover">
<thead>
<tr>
<th>ID</th><th>Book</th><th>Status</th><th>Borrow</th><th>Return</th><th>Due</th><th>Fine</th><th>Notes</th><th>Actions</th>
</tr>
</thead>
<tbody>
<?php while ($row = $transactions->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['id'] ?? '0') ?></td>
    <td><?= htmlspecialchars($row['book_title'] ?? '') ?></td>
    <td><?= htmlspecialchars($row['status'] ?? '') ?></td>
    <td><?= htmlspecialchars($row['borrow_date'] ?? '-') ?></td>
    <td><?= htmlspecialchars($row['return_date'] ?? '-') ?></td>
    <td><?= htmlspecialchars($row['due_date'] ?? '-') ?></td>
    <td>₱<?= number_format((float)($row['fine_amount'] ?? 0), 2) ?></td>
    <td><?= htmlspecialchars($row['notes'] ?? '') ?></td>
    <td>

        <!-- Shift Dates Button -->
        <?php if (($row['status'] ?? '') === 'Borrowed'): ?>
        <form method="POST" class="d-inline">
            <input type="hidden" name="transaction_id" value="<?= htmlspecialchars($row['id'] ?? '0') ?>">
            <button type="submit" name="shift_dates" class="btn btn-sm btn-warning mb-1">Shift Dates</button>
        </form>
        <?php endif; ?>

        <!-- Edit Button (Modal) -->
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= htmlspecialchars($row['id'] ?? '0') ?>">Edit</button>

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal<?= htmlspecialchars($row['id'] ?? '0') ?>" tabindex="-1">
        <div class="modal-dialog">
        <div class="modal-content">
        <form method="POST" class="p-3">
            <input type="hidden" name="transaction_id" value="<?= htmlspecialchars($row['id'] ?? '0') ?>">
            <input type="hidden" name="edit_transaction" value="1">
            <div class="mb-2">
                <label>Status</label>
                <select name="status" class="form-select" required>
                    <?php
                    $statuses = ['Reserved','Borrowed','Returned','Cancelled','Overdue','Lost','Completed'];
                    foreach ($statuses as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= ($row['status'] ?? '') === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-2">
                <label>Fine Amount</label>
                <input type="number" step="0.01" name="fine_amount" class="form-control" value="<?= number_format((float)($row['fine_amount'] ?? 0), 2) ?>">
            </div>
            <div class="mb-2">
                <label>Notes</label>
                <textarea name="notes" class="form-control"><?= htmlspecialchars($row['notes'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-success">Save</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </form>
        </div>
        </div>
        </div>

    </td>
</tr>
<?php endwhile; ?>
</tbody>

</table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>