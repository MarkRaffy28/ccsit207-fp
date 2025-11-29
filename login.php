<?php
  session_start();
  
  include "config.php";
  include "components.php";  
  
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = test_input($_POST["username"]);
    $password = test_input($_POST["password"]);
    
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $hashed_password);
    
    if ($stmt->fetch()) {
      if (password_verify($password, $hashed_password)) {
        $_SESSION["msg"] = ["success", "Login Successfully."];
        $_SESSION["id"] = $id;
        
        header("Location: index.php");
        exit;
      } else {
        $_SESSION["msg"] = ["danger", "Invalid username or password."];
      }
    } else {
      $_SESSION["msg"] = ["danger", "Invalid username or password."];
    }
    $stmt->close();
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="shortcut icon" href="https://ispsc.edu.ph/file-manager/images/ispsc_logo_2.png" type="image/jpg">
  <title>Login | ISPSC Library Management System</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://kit.fontawesome.com/69faae9203.js" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="stylesheet.css">
  <script defer src="javascript.js?v=<?= time(); ?>"></script>
  <style>
    main {
      min-height: 100vh;
      background:
        linear-gradient(rgba(255,255,255,0.3), rgba(255,255,255,0.3)), url("https://miro.medium.com/v2/resize:fit:1200/1*6Jp3vJWe7VFlFHZ9WhSJng.jpeg");
      background-position: center;
      background-size: cover;
      background-repeat: no-repeat;
    }
  </style>
</head>
<body>
  <main>
    <div class="modal show" style="display: block !important">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable p-4">
        <div class="modal-content">
          <div class="modal-header pb-0 border-0">          
            <h4 class="modal-title w-100 text-center fw-bold m-0 p-0">LOGIN</h4>  
          </div>
          
          <div class="modal-body">
            <?= showAlert(); ?>
            <form method="POST" novalidate>
              <div class="row mb-2">
                <div class="col-sm form-floating">
                  <input type="text" class="form-control" id="username" name="username" placeholder="Username" pattern="[A-Za-z0-9._]+" required>
                  <label for="username" class="form-label ps-4">Username</label>
                </div>
              </div>
              <div class="row mb-2">
                <div class="col-sm form-floating">
                  <input type="password" class="form-control" id="password" name="password" placeholder="Password" pattern="[A-Za-z0-9@$!%*?&._]+" required>
                  <label for="password" class="form-label ps-4">Password</label>
                  <i class="bi bi-eye fs-4 eye"></i>
                </div>
              </div>
              <div class="row m-2">
                <!-- <span class="text-end fs-6">Don't Have an Account? <a href="signup.php" class="link-primary d-inline-block">Sign Up</a></span> -->
                <div class="d-flex justify-content-center mb-2">
                  <input type="submit" value="Login" class="btn btn-success mt-4">
                </div>
              </div>    
            </form>
          </div>
        </div>
      </div>
    </div>
  </main>
</body>
</html>