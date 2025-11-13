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
  
  $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["change_username"])) {
      $new_username = test_input($_POST["new_username"]);
      
      if ($new_username == $row["username"]) {
        $_SESSION["msg"] = ["danger", "You are already using this username."];
      }
      
      $stmt_chck_usrnm = $conn->prepare("SELECT * FROM users WHERE username = ?");
      $stmt_chck_usrnm->bind_param("s", $new_username);
      $stmt_chck_usrnm->execute();
      $result_chck_usrnm = $stmt_chck_usrnm->get_result();
      
      if ($result_chck_usrnm->num_rows > 0 || $new_username == "admin") {
        $_SESSION["msg"] = ["danger", "Username already exists."];
        header ("Location: " . $_SERVER["PHP_SELF"]);
        exit;
      }
        
      $stmt_updt_usrnm = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
      $stmt_updt_usrnm->bind_param("si", $new_username, $user_id);
        
      if (!$stmt_updt_usrnm->execute()) {
        $_SESSION["msg"] = ["danger", "Update error. Please try again later."];
      }
      $_SESSION["msg"] = ["success", "Username updated successfully"];
      header ("Location: " . $_SERVER["PHP_SELF"]);
      exit;
    }
    
    if (isset($_POST["change_password"])) {
      $old_password = test_input($_POST["old_password"]);
      $new_password = test_input($_POST["new_password"]);
      $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
      
      if (!password_verify($old_password, $row["password"])) {
        $_SESSION["msg"] = ["danger", "Incorrect password."];
      }
      
      if (password_verify($new_password, $row["password"])) {
        $_SESSION["msg"] = ["danger", "You are already using this password."];
      }
      
      $stmt_updt_psswrd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
      $stmt_updt_psswrd->bind_param("si", $hashed_password, $user_id);
      
      if (!$stmt_updt_psswrd->execute()) {
        $_SESSION["msg"] = ["danger", "Update error. Please try again later."];
      }
      $_SESSION["msg"] = ["success", "Password updated successfully"];
      header ("Location: " . $_SERVER["PHP_SELF"]);
      exit;
    }
        
    if (isset($_POST["edit_profile"])) {
      $first_name = test_input($_POST["first_name"]);
      $middle_name = test_input($_POST["middle_name"]);
      $last_name = test_input($_POST["last_name"]);
      $extension_name = test_input($_POST["extension_name"]);
      $age = test_input($_POST["age"]);
      $gender = test_input($_POST["gender"]);
      $birth_date = test_input($_POST["birth_date"]);
      $contact_number = test_input($_POST["contact_number"]);
      $email_address = test_input($_POST["email_address"]);
      $address = test_input($_POST["address"]);
      
      $stmt_updt_profile = $conn->prepare("UPDATE users SET
          first_name = ?, 
          middle_name = ?, 
          last_name =  ?, 
          extension_name = ?, 
          age = ?, 
          gender = ?, 
          birth_date = ?, 
          contact_number = ?, 
          email_address = ?, 
          address = ?
        WHERE id = ?");
      $stmt_updt_profile->bind_param("ssssisssssi", $first_name, $middle_name, $last_name, $extension_name, $age, $gender, $birth_date, $contact_number, $email_address, $address, $user_id);
      
      if (!$stmt_updt_profile->execute()) {
        $_SESSION["msg"] = ["danger", "Update error. Please try again later."];
      }
      $_SESSION["msg"] = ["success", "Profile information updated successfully"];
      header ("Location: " . $_SERVER["PHP_SELF"]);
      exit;    
    }
    
    if (isset($_POST["delete_account"])) {
      $stmt_del_acc = $conn->prepare("DELETE FROM users WHERE id = ?");
      $stmt_del_acc->bind_param("i", $user_id);
      
      if (!$stmt_del_acc->execute()) {
        $_SESSION["msg"] = ["danger", "Delete error. Please try again later."];
      }
      $_SESSION["msg"] = ["success", "Account deleted successfully"];
      session_unset();
      session_destroy();
      header ("Location: " . $_SERVER["PHP_SELF"]);
      exit;
    }
  }
  
  showHeader("Profile")
?>

<main class="py-4">
  <?= showAlert(); ?>
  <section class="profile row mx-3 mx-lg-5">
    <div class="col-lg-4">
      <h4 class="mb-3 fw-semibold"><i class="fa-solid fa-gear"></i> Account Settings</h4>
      <div class="col-lg-4 d-flex justify-content-center align-items-center profile-picture mx-auto">
        <?php if($row["profile_picture"]): ?>
          <img src="data:image/jpeg;base64,<?= base64_encode($row["profile_picture"]); ?>" class="rounded-circle">
        <?php else: ?>
          <i class="bi bi-person-circle"></i>
        <?php endif; ?>
      </div>
      
      <div class="card rounded-4 mt-5">
        <div class="profile-change-chevron d-flex justify-content-between align-items-center px-3 py-2" type="button" data-bs-toggle="modal" data-bs-target="#change_username">
          <h6 class="m-0">Change Username</h6>
          <button class="btn" ><i class="bi bi-chevron-right"></i></button>
        </div>
        <hr class="m-0">
        <div class="profile-change-chevron d-flex justify-content-between align-items-center px-3 py-2" type="button" data-bs-toggle="modal" data-bs-target="#change_password">
          <h6 class="m-0">Change Password</h6>
          <button class="btn" ><i class="bi bi-chevron-right"></i></button>
        </div>
      </div>
    </div>  
    
    <div class="col-lg-8">
      <h4 class="mb-3 fw-semibold"><i class="fa-solid fa-info-circle"></i> Profile Information</h4>
      <div class="row mb-2 gx-3 gy-2 gy-lg-0">
        <div class="col-sm">
          <label class="form-label">First Name</label>
          <input type="text" class="form-control" id="first_name" value="<?= htmlspecialchars($row["first_name"]); ?>" readonly>
        </div>
        <div class="col-sm">
          <label class="form-label">Middle Name</label>
          <input type="text" class="form-control" id="middle_name" value="<?= htmlspecialchars($row["middle_name"]); ?>" readonly>
        </div>
      </div>
      
      <div class="row mb-2 gx-3 gy-2 gy-lg-0">
        <div class="col-sm">
          <label class="form-label">Last Name</label>
          <input type="text" class="form-control" id="last_name" value="<?= htmlspecialchars($row["last_name"]); ?>" readonly>
        </div>
        <div class="col-sm">
          <label class="form-label">Extension Name</label>
          <input type="text" class="form-control" id="extension_name" value="<?= htmlspecialchars($row["extension_name"]); ?>" readonly>
        </div>
      </div>
      
      <div class="row mb-2 gx-3 gy-2 gy-lg-0">
        <div class="col-sm">
          <label class="form-label">Age</label>
          <input type="number" class="form-control" id="age" value="<?= htmlspecialchars($row["age"]); ?>" readonly>
        </div>
        <div class="col-sm">
          <label class="form-label">Gender</label>
          <input type="text" class="form-control" id="gender" value="<?= htmlspecialchars($row["gender"]); ?>" readonly>
        </div>
      </div>
      
      <div class="row mb-2 gx-3 gy-2 gy-lg-0">
        <div class="col-sm">
          <label class="form-label">Birth Date</label>
          <input type="date" class="form-control" id="birth_date" value="<?= date('Y-m-d', strtotime($row['birth_date'])); ?>" readonly>
        </div>
        <div class="col-sm">
          <label class="form-label">Contact Number</label>
          <input type="tel" class="form-control" id="contact_number" value="<?= htmlspecialchars($row["contact_number"]); ?>" readonly>
        </div>
      </div>
      
      <div class="row mb-2 gx-3 gy-2 gy-lg-0">
        <div class="col-sm">
          <label class="form-label">Email Address</label>
          <input type="email" class="form-control" id="email_address" value="<?= htmlspecialchars($row["email_address"]); ?>" readonly>
        </div>
        <div class="col-sm">
          <label class="form-label">Address</label>
          <textarea rows="1" class="form-control" id="address" readonly> <?= htmlspecialchars($row["address"]); ?> </textarea>
        </div>
      </div>
      
      <div class="d-flex justify-content-end mt-4">
        <button class="btn btn-primary me-3" data-bs-toggle="modal" data-bs-target="#edit_profile"><i class="fa-solid fa-pen-to-square"></i> Edit Information</button>
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#delete_account"><i class="fa-solid fa-trash"></i> Delete Account</button>
      </div>
    </div>
  </section>
  
  
  <div class="modal fade" id="change_username" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable p-4">
      <div class="modal-content">
        <div class="modal-header pb-0 border-0">          
          <h4 class="modal-title w-100 text-center fw-bold m-0 p-0">CHANGE USERNAME</h4>  
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>      
        <div class="modal-body">
          <form method="POST" novalidate>
            <div class="row mb-2">
              <div class="col-sm form-floating">
                <input type="text" class="form-control" id="new_username" name="new_username" placeholder="New Username" pattern="[A-Za-z0-9._]+" required>
                <label for="new_username" class="form-label ps-4">New Username</label>
              </div>
            </div>
            <div class="row m-2">
              <div class="d-flex justify-content-center mt-4 mb-2">
                <button type="button" class="btn btn-md btn-danger rounded-3 px-3 me-3" data-bs-dismiss="modal">Cancel</button>
                <input type="submit" name="change_username" value="Update" class="btn btn-success">
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  
  <div class="modal fade" id="change_password" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable p-4">
      <div class="modal-content">
        <div class="modal-header pb-0 border-0">          
          <h4 class="modal-title w-100 text-center fw-bold m-0 p-0">CHANGE PASSWORD</h4>  
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>         
        <div class="modal-body">
          <form method="POST" novalidate>
            <div class="row mb-2">
              <div class="col-sm form-floating">
                <input type="password" class="form-control input-password" id="old_password" name="old_password" placeholder="Old Password" pattern="[A-Za-z0-9@$!%*?&._]+" required>
                <label for="old_password" class="form-label ps-4">Old Password</label>
                <i class="bi bi-eye fs-4 eye"></i>
              </div>
            </div>        
            <div class="row mb-2">
              <div class="col-sm form-floating">
                <input type="password" class="form-control input-password" id="new_password" name="new_password" placeholder="New Password" pattern="[A-Za-z0-9@$!%*?&._]+" required>
                <label for="new_password " class="form-label ps-4">New Password</label>
                <i class="bi bi-eye fs-4 eye"></i>
              </div>
            </div>              
            <div class="row m-2">
              <div class="d-flex justify-content-center mt-4 mb-2">
                <button type="button" class="btn btn-md btn-danger rounded-3 px-3 me-3" data-bs-dismiss="modal">Cancel</button>
                <input type="submit" name="change_password" value="Update" class="btn btn-success">
              </div>
            </div>    
          </form>
        </div>
      </div>
    </div>
  </div>
  
  <div class="modal fade" id="edit_profile" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable p-4">
      <div class="modal-content">
        <div class="modal-header pb-0 border-0">          
          <h4 class="modal-title w-100 text-center fw-bold m-0 p-0">EDIT INFORMATION</h4>  
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>         
        <div class="modal-body">            
          <form method="POST" novalidate>
            <div class="row mb-2 gx-3 gy-2">
              <div class="col-sm form-floating">
                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name" value="<?= htmlspecialchars($row["first_name"]); ?>" required>
                <label for="first_name" class="form-label ps-4">First Name</label>
            </div>
            <div class="col-sm form-floating">
              <input type="text" class="form-control" id="middle_name" name="middle_name" placeholder="Middle Name" value="<?= htmlspecialchars($row["middle_name"]); ?>" required>
              <label for="middle_name" class="form-label ps-4">Middle Name</label>
            </div>
          </div>
              
          <div class="row mb-2 gx-3 gy-2">
            <div class="col-sm form-floating">
              <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name" value="<?= htmlspecialchars($row["last_name"]); ?>" required>
              <label for="last_name" class="form-label ps-4">Last Name</label>
              </div>
              <div class="col-sm form-floating">
                <input type="text" class="form-control" id="extension_name" name="extension_name" placeholder="Extension Name" value="<?= htmlspecialchars($row["extension_name"]); ?>">
                <label for="extension_name" class="form-label ps-4">Extension Name</label>
              </div>
            </div>
              
            <div class="row mb-2 gx-3 gy-2">
              <div class="col-sm form-floating">
                <input type="number" class="form-control" id="age" name="age" placeholder="Age" value="<?= htmlspecialchars($row["age"]); ?>" required>
                <label for="age" class="form-label ps-4">Age</label>
              </div>
              <div class="col-sm form-floating">
                <select id="gender" name="gender" class="form-select ps-4" required>
                  <option <?= $row["gender"] == "Male" ? "selected" : ""?> value="Male">Male</option>
                  <option <?= $row["gender"] == "Female" ? "selected" : ""?> value="Female">Female</option>
                  <option <?= $row["gender"] == "Other" ? "selected" : ""?> value="Other">Other</option>
                </select>
                <label for="gender" class="form-label ps-4">Select Gender</label>
              </div>
            </div>
              
              <div class="row mb-2 gx-3 gy-2">
              <div class="col-sm form-floating">
                <input type="date" class="form-control" id="birth_date" name="birth_date" placeholder="Birth Date" value="<?= date('Y-m-d', strtotime($row['birth_date'])); ?>" required>
                <label for="birth_date" class="form-label ps-4">Birth Date</label>
              </div>
              <div class="col-sm form-floating">
                <input type="tel" class="form-control" id="contact_number" name="contact_number" placeholder="Contact Number (e.g. 09...)" value="<?= htmlspecialchars($row["contact_number"]); ?>" required pattern="\d{11}" minlength="11" maxlength="11">
                <label for="contact_number" class="form-label  ps-4">Contact Number (e.g. 09...)</label>
              </div>
            </div>
              
            <div class="row mb-2 gx-3 gy-2">
              <div class="col-sm form-floating">
                <input type="email" class="form-control" id="email_address" name="email_address" placeholder="E-mail Address" value="<?= htmlspecialchars($row["email_address"]); ?>" required>
                <label for="email_address" class="form-label ps-4">E-mail Address</label>
              </div>
              <div class="col-sm form-floating">
                <textarea class="form-control" id="address" name="address" placeholder="Address" required> <?= htmlspecialchars($row["address"]); ?> </textarea>
                <label for="address" class="form-label ps-4">Address</label>
              </div>
            </div>
            
            <div class="row m-2">
              <div class="d-flex justify-content-center mt-4 mb-2">
                <button type="button" class="btn btn-md btn-danger rounded-3 px-3 me-3" data-bs-dismiss="modal">Cancel</button>
                <input type="submit" name="edit_profile" value="Update" class="btn btn-success">
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  
  <div class="modal fade p-4" id="delete_account" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content rounded-4 shadow">
        <div class="modal-header border-0">
          <h5 class="modal-title w-100 text-center fw-bold">DELETE?</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>     
        <div class="modal-body text-center">
          <i class="fa-solid fa-triangle-exclamation fa-4x text-danger mb-3"></i>
          <p class="mb-0">Are you sure you want to delete your account? This action cannot be undone.</p>
        </div>      
        <div class="modal-footer border-0 d-flex justify-content-center">
          <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
          <form method="POST">
            <input type="submit" name="delete_account" value="Yes, Delete" class="btn bg-danger text-light rounded-3 px-4">
          </form>
        </div>
      </div>
    </div>
  </div>
</main>

<?php
  showFooter();
?>