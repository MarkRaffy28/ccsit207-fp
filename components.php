<?php 
  function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  } 
  
  //
  function includeLinkScript() {
?>
    <link rel="shortcut icon" href="https://ispsc.edu.ph/file-manager/images/ispsc_logo_2.png" type="image/jpg">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/69faae9203.js" crossorigin="anonymous"></script>
    
    <link rel="stylesheet" href="stylesheet.css">
    <script defer src="javascript.js"></script>
<?php
  }
  
//
  function showHeader($pageTitle) {
    $pageName = ($pageTitle == "Home") ? "index" : strtolower(str_replace(" ","_",$pageTitle));
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width,  initial-scale=1.0"/>
      <title> <?= ($pageTitle) ? "$pageTitle | ISPSC Library Management System" : "ISPSC Library Management System" ?> </title>
      <?php includeLinkScript(); ?>
    </head>
    <body>
      <nav class="navbar navbar-expand-lg sticky-top" data-bs-theme="dark">
        <div class="container-fluid">
          <a class="navbar-brand" href="index.php">
            <img src="https://ispsc.edu.ph/file-manager/images/ispsc_logo_2.png" alt="logo" class="logo">
            <span class="fw-bold navbar-title">ISPSC Library Management System</span>
          </a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-content">
            <i class="fa-solid fa-burger fa-1.5x"></i>
          </button>
          <div class="collapse navbar-collapse" id="navbar-content">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
              <li class="nav-item">
                <a class="nav-link <?= ($pageName == "index") ? "active" : "" ?> fw-bold" href="index.php">Home</a>
              </li>
              <li class="nav-item <?= (!isset($_SESSION["id"])) ? "d-none" : "" ?>">
                <a class="nav-link position-relative <?= ($pageName == 'notifications') ? 'active' : '' ?> fw-bold" href="notifications.php">
                    <?php
                      global $conn;

                      $notification_count = 0;
                      if (isset($_SESSION["id"])) {
                        $user_id = $_SESSION["id"];
                        $result = $conn->query("SELECT COUNT(id) FROM book_notifications WHERE user_id = $user_id AND is_read = 0");
                        $notification_count = $result->fetch_row()[0];
                      }
                    ?>
                    Notifications
                    <?php if ($notification_count > 0): ?>
                      <span class="badge bg-danger rounded-pill position-absolute top-75 start-100 translate-middle fs-7">
                        <?= $notification_count ?>
                      </span>
                    <?php endif; ?>
                </a>
              </li>
              <li class="nav-item <?= (!isset($_SESSION["id"])) ? "d-none" : "" ?>">
                <a class="nav-link <?= ($pageName == "profile") ? "active" : "" ?> fw-bold" href="profile.php">Profile</a>
              </li>
            </ul>
            <?php if(isset($_SESSION["id"])): ?>
              <form method="POST" action="logout.php">
                <input type="submit" value="Logout" class="btn btn-sm btn-danger ms-lg-2">
              </form>
            <?php else: ?>
              <a href="login.php" class="btn btn-sm btn-success ms-lg-2">Login</a>
            <?php endif; ?>
          </div>
        </div>
      </nav>
<?php
  }

//
  function showFooter() {
?>
      <footer class="pt-4">
        <div class="container">
          <div class="row">
            <div class="col-md-4 mb-3">
              <h5 class="fw-bold">About Us</h5>
              <p>Our clinic is dedicated to providing quality and compassionate care for every patient.</p>
            </div>
            <div class="contact-us col-md-4 mb-3">
              <h5 class="fw-bold">Contact Us</h5>
              <ul class="list-unstyled">
                <li>
                  <a href="#"><i class="fa-solid fa-location-dot"></i>Poblacion Norte, Sta. Maria, Ilocos Sur</a>     
                </li>
                <li>
                  <a href="mailto: ispsc_2705@yahoo.com"><i class="fa-solid fa-envelope"></i>ispsc_2705@yahoo.com</a>
                </li>
                <li>
                  <a href="#"><i class="fa-solid fa-phone"></i>(077)732-5512</a>
                </li>
              </ul>
            </div>
            <div class="col-md-2 mb-4"> 
              <h5 class="fw-bold">Follow Us</h5>
              <a href="https://www.facebook.com/profile.php?id=100095026794023"><i class="fa-brands fa-facebook"></i></a>
              <a href="https://www.youtube.com/@ISPSC"><i class="fa-brands fa-youtube"></i></a>
              <a href="https://x.com/ISPSC_Official"><i class="fa-brands fa-x-twitter"></i></a>
            </div>
            <div class="col-md-2 mb-3">
              <h5 class="fw-bold">Legal</h5>
              <ul class="list-unstyled">
                <li><a href="#">Privacy</a></li>
                <li><a href="#">Terms of Use</a></li>
              </ul>
            </div>
          </div>
          <div class="text-center py-3 border-top mt-3">
            © 2025 ISPSC Library Management System
          </div>
        </div>
      </footer>  
    </body>
    </html>
<?php
  }
  
  //
  function showAlert() {  
    if(isset($_SESSION["msg"])): 
      $type = $_SESSION["msg"][0];
      $message = $_SESSION["msg"][1];
      $icon = ($type == "success") ? "check-circle-fill" : "exclamation-triangle-fill"
?>
      <svg xmlns="http://www.w3.org/2000/svg" class="d-none">
        <symbol id="check-circle-fill" viewBox="0 0 16 16">
          <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" fill="currentColor"/>
        </symbol>
        <symbol id="exclamation-triangle-fill" viewBox="0 0 16 16">
          <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" fill="currentColor"/>
        </symbol>
      </svg>
      
      <div class="alert show fade m-2 alert-<?= $type; ?> d-flex align-items-center" role="alert">
        <svg width="20" height="20" class="bi text-<?= $type ?> flex-shrink-0 me-2" role="img" aria-label="Info:"><use xlink:href="#<?= $icon ?>"/></svg>
        <div class="text-<?= $type ?>"> <?= $message; ?> </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      
      <script>
        setTimeout(() => {
          var alert = document.querySelector(".alert");
          if(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
          }
        }, 3000)
      </script>
<?php
      unset($_SESSION["msg"]);
    endif;
  }
?>
