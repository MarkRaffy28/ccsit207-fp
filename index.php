<?php
  session_start();

  include("config.php");
  include("components.php");

  showHeader("Home");
?>

<main class="m-3">
  <div>
    <h3>List of Available Books</h3>
  </div>
</main>

<?php
  showFooter();
?>