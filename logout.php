<?php
  session_start();
  
  unset($_SESSION["id"]);
  unset($_SESSION["username"]);
  
  $_SESSION["msg"] = ["success", "Logged out successfully."];
  header ("Location: index.php");
  exit;
?>