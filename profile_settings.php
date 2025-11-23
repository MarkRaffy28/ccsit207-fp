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
  $is_email_verified = false;
  $show_new_password_modal = false;

  $stmt_is_email_verified = $conn->query("SELECT user_id FROM user_email_verifications WHERE user_id = $user_id");
  $is_email_verified = ($stmt_is_email_verified->num_rows > 0);

  $stmt = $conn->query("SELECT * FROM users WHERE id = $user_id");
  $row = $stmt->fetch_assoc();

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset( $_POST["change_profile_picture"])) {
      if (!empty($_FILES["new_profile_picture"]["name"])) {
        $new_profile_picture = addslashes(file_get_contents($_FILES["new_profile_picture"]["tmp_name"]));
        $conn->query("UPDATE users SET profile_picture='$new_profile_picture' WHERE id=$user_id");
      } else {
        $conn->query("UPDATE users SET profile_picture=NULL WHERE id='$user_id'");
      }

      $_SESSION["msg"] = ["success", "Profile picture updated successfully."];
      header ("Location: " . $_SERVER["PHP_SELF"]);
      exit;
    }

    if (isset($_POST["change_username"])) {
      $new_username = test_input($_POST["new_username"]);
      
      if ($new_username == $row["username"]) {
        $_SESSION["msg"] = ["danger", "You are already using this username."];
        header ("Location: " . $_SERVER["PHP_SELF"]);
        exit; 
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
        exit;
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
        exit;
      }
      
      if (password_verify($new_password, $row["password"])) {
        $_SESSION["msg"] = ["danger", "You are already using this password."];
        exit;
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
      $edit_first_name = test_input($_POST["edit_first_name"]);
      $edit_middle_name = test_input($_POST["edit_middle_name"]);
      $edit_last_name = test_input($_POST["edit_last_name"]);
      $edit_extension_name = test_input($_POST["edit_extension_name"]);
      $edit_gender = test_input($_POST["edit_gender"]);
      $edit_program = test_input($_POST["edit_program"]);
      $edit_user_id = test_input($_POST["edit_user_id"]);
      $edit_major = test_input($_POST["edit_major"]);
      $edit_strand = test_input($_POST["edit_strand"]);
      $edit_year_section = test_input($_POST["edit_year_section"]);
      $edit_birth_date = test_input($_POST["edit_birth_date"]);
      $edit_contact_number = test_input($_POST["edit_contact_number"]);
      $edit_email_address = test_input($_POST["edit_email_address"]);
      $edit_address = test_input($_POST["edit_address"]);
      
      $stmt_updt_profile = $conn->prepare("UPDATE users SET
          first_name = ?, 
          middle_name = ?, 
          last_name =  ?, 
          extension_name = ?, 
          gender = ?, 
          program = ?,
          user_id = ?,
          major = ?,
          strand = ?,
          year_section = ?,
          birth_date = ?, 
          contact_number = ?, 
          email_address = ?, 
          address = ?
        WHERE id = ?");
      $stmt_updt_profile->bind_param("ssssssssssssssi", $edit_first_name, $edit_middle_name, $edit_last_name, $edit_extension_name, $edit_gender, $edit_program, $edit_user_id, $edit_major, $edit_strand, $edit_year_section, $edit_birth_date, $edit_contact_number, $edit_email_address, $edit_address, $user_id);
      
      if (!$stmt_updt_profile->execute()) {
        $_SESSION["msg"] = ["danger", "Update error. Please try again later."];
        exit;
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
        exit;
      }
      $_SESSION["msg"] = ["success", "Account deleted successfully"];
      session_unset();
      session_destroy();
      header ("Location: " . $_SERVER["PHP_SELF"]);
      exit;
    }

    if (isset($_POST["verify_email_verified"])) {
      $stmt = $conn->query("INSERT INTO user_email_verifications(user_id) VALUES($user_id)");
      $_SESSION["msg"] = ["success","E-mail verified successfully"];
      header ("Location: " . $_SERVER["PHP_SELF"]);
      exit;
    }

    if (isset($_POST["forgot_password_verified"])) {
      $show_new_password_modal = true;
    }

    if (isset($_POST["change_password_forgot"])) {
      $new_password = test_input($_POST["new_password_forgot"]);
      $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

      $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
      $stmt->bind_param("si", $hashed_new_password, $user_id);
      
      if (!$stmt->execute()) {
        $_SESSION["msg"] = ["danger", "Update error. Please try again later."];
      }
      $_SESSION["msg"] = ["success", "Password updated successfully"];
      header ("Location: " . $_SERVER["PHP_SELF"]);
      exit;
    }
  }
  
  showHeader("Profile Settings")
?>

<main class="px-lg-5 py-4">
  <?= showAlert(); ?>
  <section class="profile row mx-3 mx-lg-5">
    <div class="col-12 col-lg-4">
      <h4 class="mb-3 fw-semibold"><i class="fa-solid fa-gear"></i> Account Settings</h4>
      <h5 class="text-center mb-3">Hi, <span class="text-success fw-semibold"> <?= htmlspecialchars($row["username"]); ?> </span></h5>
      <div class="col-lg-4 d-flex justify-content-center align-items-center profile-picture mx-auto">
        <?php if($row["profile_picture"]): ?>
          <img src="data:image/jpeg;base64,<?= base64_encode($row["profile_picture"]); ?>" class="rounded-circle">
        <?php else: ?>
          <i class="bi bi-person-circle text-muted"></i>
        <?php endif; ?>
      </div>
    
      <div class="card rounded-4 mt-4">
        <div class="profile-change-chevron d-flex align-items-center px-3 py-2" type="button" data-bs-toggle="modal" data-bs-target="#change_profile_picture">
          <i class="bi bi-camera"></i>
          <h6 class="m-0 ms-2 me-auto">Change Profile Picture</h6>
          <button class="btn" ><i class="bi bi-chevron-right"></i></button>
        </div>
        <hr class="m-0">
        <div class="profile-change-chevron d-flex align-items-center px-3 py-2" type="button" data-bs-toggle="modal" data-bs-target="#change_username">
          <i class="bi bi-person-badge"></i>
          <h6 class="m-0 ms-2 me-auto">Change Username</h6>
          <button class="btn" ><i class="bi bi-chevron-right"></i></button>
        </div>
        <hr class="m-0">
        <div class="profile-change-chevron d-flex align-items-center px-3 py-2" type="button" data-bs-toggle="modal" data-bs-target="#change_password">
          <i class="bi bi-shield-lock"></i>
          <h6 class="m-0 ms-2 me-auto">Change Password</h6>
          <button class="btn" ><i class="bi bi-chevron-right"></i></button>
        </div>
        <hr class="m-0">
        <div class="profile-change-chevron d-flex align-items-center px-3 py-2" type="button" data-bs-toggle="modal" data-bs-target="#edit_profile_information">
          <i class="bi bi-pencil-square"></i>
          <h6 class="m-0 ms-2 me-auto">Edit Profile Information</h6>
          <button class="btn" ><i class="bi bi-chevron-right"></i></button>
        </div>
        <hr class="m-0">
        <div class="profile-change-chevron d-flex align-items-center px-3 py-2" type="button" data-bs-toggle="modal" data-bs-target="#delete_account">
          <i class="bi bi-trash text-danger"></i>
          <h6 class="m-0 ms-2 me-auto text-danger">Delete Account</h6>
          <button class="btn" ><i class="bi bi-chevron-right"></i></button>
        </div>
      </div>
    </div>  
    
    <div class="col-12 col-lg-8 mt-5 mt-lg-0 px-lg-5">
      <h4 class="mb-3 fw-semibold"><i class="fa-solid fa-info-circle"></i> Profile Information</h4>
      <div class="info-row">
        <div class="info-label"><i class="bi bi-person me-2"></i> Full Name</div>
        <div class="info-value"><?= $row["first_name"] . " " . $row["middle_name"] . " " . $row["last_name"] . " " . $row["extension_name"] ?></div>
      </div>
      
      <div class="info-row">
        <div class="info-label"><i class="bi bi-gender-ambiguous me-2"></i> Gender</div>
        <div class="info-value"><?= $row["gender"] ?></div>
      </div>
      
      <div class="info-row">
        <div class="info-label"><i class="bi bi-mortarboard me-2"></i> Program / Position</div>
        <div class="info-value"><?= $row["program"] ?></div>
      </div>
      
      <div class="info-row">
        <div class="info-label"><i class="bi bi-card-text me-2"></i> User ID</div>
        <div class="info-value"><?= $row["user_id"] ?></div>
      </div>
      
      <div class="info-row <?= ($row["major"] == "N/A") ? "d-none" : "" ?>">
        <div class="info-label"><i class="bi bi-book me-2"></i> Major</div>
        <div class="info-value"><?= $row["major"] ?></div>
      </div>
      
      <div class="info-row <?= ($row["strand"] == "N/A") ? "d-none" : "" ?>">
        <div class="info-label"><i class="bi bi-diagram-3 me-2"></i> Strand</div>
        <div class="info-value"><?= $row["strand"] ?></div>
      </div>
      
      <div class="info-row <?= ($row["year_section"] == "N/A") ? "d-none" : "" ?>">
        <div class="info-label"><i class="bi bi-calendar-check me-2"></i> Year & Section</div>
        <div class="info-value"><?= $row["year_section"] ?></div>
      </div>
      
      <div class="info-row">
        <div class="info-label"><i class="bi bi-cake2 me-2"></i> Birth Date</div>
        <div class="info-value"><?= date("F j, Y", strtotime($row["birth_date"])) ?></div>
      </div>
      
      <div class="info-row">
        <div class="info-label"><i class="bi bi-telephone me-2"></i> Contact Number</div>
        <div class="info-value"><?= $row["contact_number"] ?></div>
      </div>
      
      <div class="info-row">
        <div class="info-label"><i class="bi bi-envelope me-2"></i> Email Address</div>
        <div class="info-value">
          <?php 
            echo $row["email_address"];
            if ($is_email_verified):
          ?>
            <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Verified</span>
          <?php else: ?>
            <button class="otp-verify btn btn-sm btn-success ms-2 py-0 <?= (!$row["email_address"]) ? "d-none" : "" ?>" data-email="<?= $row["email_address"] ?>" data-action="verify_email">Verify</button>
          <?php endif; ?>
        </div>
      </div>
      
      <div class="info-row">
        <div class="info-label"><i class="bi bi-house me-2"></i> Address</div>
        <div class="info-value"><?= $row["address"] ?></div>
      </div>
    </div>
  </section>
  
  
  <div class="modal fade" id="change_profile_picture" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable p-4">
      <div class="modal-content">
        <div class="modal-header pb-0 border-0">          
          <h4 class="modal-title w-100 text-center fw-bold m-0 p-0">CHANGE PROFILE PICTURE</h4>  
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>      
        <div class="modal-body">
          <form method="POST" enctype="multipart/form-data" novalidate>
            <div class="row mb-2">
              <div class="col-sm">
                <label for="new_profile_picture" class="form-label">New Profile Picture</label>
                <input type="file" class="form-control" id="new_profile_picture" name="new_profile_picture" >
                <div class="form-text ps-2">Leave blank to remove current profile picture</div>
              </div>
            </div>
            <div class="row m-2">
              <div class="d-flex justify-content-center mt-4 mb-2">
                <button type="button" class="btn btn-md btn-danger rounded-3 px-3 me-3" data-bs-dismiss="modal">Cancel</button>
                <input type="submit" name="change_profile_picture" value="Update" class="btn btn-success">
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  
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
                <label for="new_password" class="form-label ps-4">New Password</label>
                <i class="bi bi-eye fs-4 eye"></i>
              </div>
            </div>
            <div class="d-flex justify-content-end">
              <a class="otp-verify link" data-email="<?= $row["email_address"] ?>" data-action="forgot_password">Forgot Password?</a>
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

  <div class="modal fade" id="new_password_modal" tabindex="-1">
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
                <input type="password" class="form-control input-password" id="new_password_forgot" name="new_password_forgot" placeholder="New Password" pattern="[A-Za-z0-9@$!%*?&._]+" required>
                <label for="new_password_forgot" class="form-label ps-4">New Password</label>
                <i class="bi bi-eye fs-4 eye"></i>
              </div>
            </div>
            <div class="row m-2">
              <div class="d-flex justify-content-center mt-4 mb-2">
                <button type="button" class="btn btn-md btn-danger rounded-3 px-3 me-3" data-bs-dismiss="modal">Cancel</button>
                <input type="submit" name="change_password_forgot" value="Update" class="btn btn-success">
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php if ($show_new_password_modal): ?>
      <script>
        document.addEventListener("DOMContentLoaded", () => {
          document.querySelectorAll('.modal.show').forEach(modalEl => {
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
          });
  
          const newPasswordModal = new bootstrap.Modal(document.getElementById("new_password_modal"));
          newPasswordModal.show();
        });
      </script>
    <?php endif; ?>
  </div>
  
  <div class="modal fade" id="edit_profile_information" tabindex="-1">
    <div class="modal-lg modal-dialog modal-dialog-centered modal-dialog-scrollable p-4">
      <div class="modal-content">
        <div class="modal-header pb-0 border-0">          
          <h4 class="modal-title w-100 text-center fw-bold m-0 p-0">EDIT PROFILE INFORMATION</h4>  
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>         
        <div class="modal-body">            
          <form method="POST" novalidate>
            <div class="row mb-2 gx-3 gy-2">
              <div class="col-sm form-floating">
                <input type="text" class="form-control" id="edit_first_name" name="edit_first_name" placeholder="First Name" value="<?= htmlspecialchars($row["first_name"]); ?>" required>
                <label for="edit_first_name" class="form-label ps-4">First Name</label>
            </div>
            <div class="col-sm form-floating">
              <input type="text" class="form-control" id="edit_middle_name" name="edit_middle_name" placeholder="Middle Name" value="<?= htmlspecialchars($row["middle_name"]); ?>" required>
              <label for="edit_middle_name" class="form-label ps-4">Middle Name</label>
            </div>
          </div>
              
          <div class="row mb-2 gx-3 gy-2">
            <div class="col-sm form-floating">
              <input type="text" class="form-control" id="edit_last_name" name="edit_last_name" placeholder="Last Name" value="<?= htmlspecialchars($row["last_name"]); ?>" required>
              <label for="edit_last_name" class="form-label ps-4">Last Name</label>
              </div>
              <div class="col-sm form-floating">
                <input type="text" class="form-control" id="edit_extension_name" name="edit_extension_name" placeholder="Extension Name" value="<?= htmlspecialchars($row["extension_name"]); ?>">
                <label for="edit_extension_name" class="form-label ps-4">Extension Name</label>
              </div>
            </div>
            
            <div class="row mb-2 gx-3 gy-2 gy-lg-0">
              <div class="col-sm form-floating">
                <select id="edit_gender" name="edit_gender" class="form-select ps-4" required>
                  <option <?= $row["gender"] == "Male" ? "selected" : ""?> value="Male">Male</option>
                  <option <?= $row["gender"] == "Female" ? "selected" : ""?> value="Female">Female</option>
                  <option <?= $row["gender"] == "Other" ? "selected" : ""?> value="Other">Other</option>
                </select>
                <label for="edit_gender" class="form-label ps-4">Select Gender</label>
              </div>
              <div class="col-sm form-floating">
                <input type="text" class="form-control" id="edit_program" name="edit_program" placeholder="Program (if student) / Position" value="<?= htmlspecialchars($row['program']); ?>" required>
                <label for="edit_program" class="form-label ps-4">Program (if student) / Position</label>
              </div>
            </div>
            
            <div class="row mb-2 gx-3 gy-2 gy-lg-0">
              <div class="col-sm form-floating">
                <input type="text" class="form-control" id="edit_user_id" name="edit_user_id" placeholder="User Id" value="<?= htmlspecialchars($row["user_id"]); ?>">
                <label for="edit_user_id" class="form-label ps-4">User ID</label>
              </div>
              <div class="col-sm form-floating">
                <input type="text" class="form-control" id="edit_major" name="edit_major" placeholder="Major" value="<?= htmlspecialchars($row['major']); ?>">
                <label for="edit_major" class="form-label ps-4">Major</label>
              </div>
            </div>

            <div class="row mb-2 gx-3 gy-2 gy-lg-0">
              <div class="col-sm form-floating">
                <input type="text" class="form-control" id="edit_strand" name="edit_strand" placeholder="Strand" value="<?= htmlspecialchars($row["strand"]); ?>">
                <label for="edit_strand" class="form-label ps-4">Strand</label>
              </div>     
              <div class="col-sm form-floating">
                <input type="text" class="form-control" id="edit_year_section" name="edit_year_section" placeholder="Year & Section" value="<?= htmlspecialchars($row["year_section"]); ?>">
                <label for="edit_strand" class="form-label ps-4">Year & Section</label>
              </div>     
            </div>

              
            <div class="row mb-2 gx-3 gy-2">
              <div class="col-sm form-floating">
                <input type="date" class="form-control" id="edit_birth_date" name="edit_birth_date" placeholder="Birth Date" value="<?= date('Y-m-d', strtotime($row['birth_date'])); ?>" required>
                <label for="edit_birth_date" class="form-label ps-4">Birth Date</label>
              </div>
              <div class="col-sm form-floating">
                <input type="tel" class="form-control" id="edit_contact_number" name="edit_contact_number" placeholder="Contact Number (e.g. 09...)" value="<?= htmlspecialchars($row["contact_number"]); ?>" required pattern="\d{11}" minlength="11" maxlength="11">
                <label for="edit_contact_number" class="form-label  ps-4">Contact Number (e.g. 09...)</label>
              </div>
            </div>
              
            <div class="row mb-2 gx-3 gy-2">
              <div class="col-sm form-floating">
                <input type="email" class="form-control" id="edit_email_address" name="edit_email_address" placeholder="E-mail Address" value="<?= htmlspecialchars($row["email_address"]); ?>" required>
                <label for="edit_email_address" class="form-label ps-4">E-mail Address</label>
              </div>
              <div class="col-sm form-floating">
                <textarea class="form-control" id="edit_address" name="edit_address" placeholder="Address" required> <?= htmlspecialchars($row["address"]); ?> </textarea>
                <label for="edit_address" class="form-label ps-4">Address</label>
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
          <h5 class="modal-title w-100 text-center fw-bold">DELETE ACCOUNT?</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>     
        <div class="modal-body text-center">
          <i class="fa-solid fa-triangle-exclamation fa-4x text-danger mb-3"></i>
          <p class="mb-0">Are you sure you want to delete your account? This action cannot be undone.</p>
        </div>      
        <div class="modal-footer border-0 d-flex justify-content-center">
          <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
          <form method="POST">
            <input type="submit" name="delete_account" value="Yes, Delete" class="btn bg-danger text-light rounded-3 px-4">
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade p-4" id="otp_modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content rounded-4 shadow">
        <div class="modal-header border-0">
          <h5 class="modal-title w-100 text-center fw-bold">ENTER VERIFICATION CODE</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>     
        <div class="modal-body text-center">
          <form id="otp_form" class="d-flex flex-column align-items-center">
            <div class="otp-container">
              <input type="text" maxlength="1" class="form-control otp-input" pattern="\d*" disabled required>
              <input type="text" maxlength="1" class="form-control otp-input" pattern="\d*" disabled required>
              <input type="text" maxlength="1" class="form-control otp-input" pattern="\d*" disabled required>
              <input type="text" maxlength="1" class="form-control otp-input" pattern="\d*" disabled required>
              <input type="text" maxlength="1" class="form-control otp-input" pattern="\d*" disabled required>
              <input type="text" maxlength="1" class="form-control otp-input" pattern="\d*" disabled required>
            </div>
            <div class="flex">
              <button id="send_otp" class="btn btn-sm btn-primary">Send OTP</button>
              <input type="submit" id="submit_otp" class="btn btn-sm btn-success ms-2 d-none">
            </div>
          </form>
          <form method="POST" id="otp_hidden_form" class="d-none">
            <input type="hidden" id="otp_hidden_input">
          </form>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".otp-verify").forEach(button => {
      button.addEventListener("click", () => {
        const email = button.dataset.email;
        const action = button.dataset.action;
        
        document.querySelectorAll('.modal.show').forEach(modalEl => {
          const modalInstance = bootstrap.Modal.getInstance(modalEl);
          if (modalInstance) modalInstance.hide();
        });

        const Modal = new bootstrap.Modal(document.getElementById("otp_modal"));
        Modal.show();

        const otpInputs = document.querySelectorAll(".otp-input");

        otpInputs.forEach((input, index) => {
          input.addEventListener("input", (e) => {
            const value = e.target.value;
            e.target.value = value.replace(/\D/, '');
            if (value.length === 1 && index < otpInputs.length - 1) {
              otpInputs[index + 1].focus();
            }
          });

          input.addEventListener("keydown", (e) => {
            if (e.key === "Backspace" && !input.value && index > 0) {
              otpInputs[index - 1].focus();
            }
          });
        });

        function generateOTP() {
          const otp = String(Math.floor(100000 + Math.random() * 900000));
          const expiresAt = Date.now() + 5 * 60 * 1000;
          localStorage.setItem("otp_code", otp);
          localStorage.setItem("otp_expiry", expiresAt);
          return otp;
        }

        function startTimer(button, remaining = 300) {
          button.disabled = true;
          const originalText = button.innerHTML;

          const interval = setInterval(() => {
            if (remaining <= 0) {
              button.disabled = false;
              button.innerHTML = originalText;
              localStorage.removeItem("otp_expiry");
              clearInterval(interval);
            } else {
              button.innerHTML = `Resend OTP (${remaining}s)`;
              remaining--;
            }
          }, 1000);
        }

        document.body.addEventListener("click", (e) => {
          if (e.target && e.target.id === "send_otp") {
            e.preventDefault();
            const button = e.target;
            const otp = generateOTP();

            const serviceID = "service_library";
            const templateID = "template_verify_email";

            emailjs.send(serviceID, templateID, { email: email, passcode: otp })
              .then(() => {
                alert("OTP sent!");
                startTimer(button);
                document.getElementById("submit_otp").classList.remove("d-none");
                otpInputs.forEach(input => {
                  input.disabled = false;
                  input.focus();
                });
              })
              .catch((err) => {
                console.error("EmailJS error:", err);
                alert("Failed to send OTP. Email may not exist or service misconfigured.");
              });
          }
        });

        document.getElementById("otp_form").addEventListener("submit", (e) => {
          e.preventDefault();

          const userOTP = Array.from(otpInputs).map(i => i.value).join("");
          const savedOTP = localStorage.getItem("otp_code");
          const expiry = Number(localStorage.getItem("otp_expiry"));

          if (Date.now() > expiry) {
            return alert("OTP has expired. Please resend.");
          }

          if (userOTP === savedOTP) {
            localStorage.removeItem("otp_code");
            localStorage.removeItem("otp_expiry");
            
            document.getElementById("otp_hidden_input").name = `${action}_verified`;
            document.getElementById("otp_hidden_form").submit();
          } else {
            alert("Incorrect OTP. Try again.");
          }
        });

      });
    });
  });
</script>

<?php
  showFooter();
?>