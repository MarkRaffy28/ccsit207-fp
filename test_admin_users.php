<?php
session_start();

include("config.php");

function showAlert($message, $type = "success") {
    echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
            $message
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
}

if (isset($_POST["add_user"])) {
    $username = $_POST["username"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $first_name = $_POST["first_name"];
    $middle_name = $_POST["middle_name"] ?? null;
    $last_name = $_POST["last_name"];
    $extension_name = $_POST["extension_name"] ?? null;
    $user_id = $_POST["user_id"];
    $birth_date = $_POST["birth_date"];
    $gender = $_POST["gender"];
    $program = $_POST["program"];
    $major = $_POST["major"] ?: 'N/A';
    $strand = $_POST["strand"] ?: 'N/A';
    $address = $_POST["address"] ?? null;
    $year_section = $_POST["year_section"];
    $contact_number = $_POST["contact_number"] ?? null;
    $email_address = $_POST["email_address"];

    $stmt = $conn->prepare("INSERT INTO users (
        username, password, first_name, middle_name, last_name, extension_name,
        user_id, birth_date, gender, program, major, strand, address, year_section,
        contact_number, email_address
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "ssssssssssssssss",
        $username, $password, $first_name, $middle_name, $last_name, $extension_name,
        $user_id, $birth_date, $gender, $program, $major, $strand, $address,
        $year_section, $contact_number, $email_address
    );

    if ($stmt->execute()) {
        showAlert("User added successfully!", "success");
    } else {
        showAlert("Error adding user: " . $stmt->error, "danger");
    }
    $stmt->close();
}

if (isset($_GET["delete"])) {
    $id = $_GET["delete"];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        showAlert("User deleted successfully!", "success");
    } else {
        showAlert("Failed to delete user.", "danger");
    }
    $stmt->close();
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Users Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>User Management</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">+ Add User</button>
    </div>

    <div class="table-responsive shadow rounded">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark text-center">
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>User ID</th>
                    <th>Program</th>
                    <th>Year & Section</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $result = $conn->query("SELECT * FROM users ORDER BY id DESC");
            if ($result->num_rows > 0) {
                $i = 1;
                while ($row = $result->fetch_assoc()) {
                    echo "<tr class='text-center'>";
                    echo "<td>{$i}</td>";
                    echo "<td>{$row['username']}</td>";
                    echo "<td>{$row['first_name']} {$row['middle_name']} {$row['last_name']} {$row['extension_name']}</td>";
                    echo "<td>{$row['user_id']}</td>";
                    echo "<td>{$row['program']}</td>";
                    echo "<td>{$row['year_section']}</td>";
                    echo "<td>{$row['email_address']}</td>";
                    echo "<td>{$row['contact_number']}</td>";
                    echo "<td>
                        <a href='?delete={$row['id']}' onclick='return confirm(\"Delete this user?\")' class='btn btn-sm btn-danger'>Delete</a>
                    </td>";
                    echo "</tr>";
                    $i++;
                }
            } else {
                echo "<tr><td colspan='9' class='text-center text-muted'>No users found</td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Middle Name</label><input type="text" name="middle_name" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Extension Name</label><input type="text" name="extension_name" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select Gender</option>
                                <option>Male</option>
                                <option>Female</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">User ID</label><input type="text" name="user_id" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Birth Date</label><input type="date" name="birth_date" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Program</label><input type="text" name="program" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Major</label><input type="text" name="major" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Strand</label><input type="text" name="strand" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Year & Section</label><input type="text" name="year_section" class="form-control" required></div>
                        <div class="col-md-12"><label class="form-label">Address</label><input type="text" name="address" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Contact Number</label><input type="text" name="contact_number" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Email Address</label><input type="email" name="email_address" class="form-control" required></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
