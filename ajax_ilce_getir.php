<?php
include "db.php";
if (isset($_POST['il'])) {
  $il = $_POST['il'];
  $result = $conn->query("SELECT ilce FROM il_ilce_limitler WHERE il='$il' ORDER BY ilce ASC");
  while ($row = $result->fetch_assoc()) {
    echo "<option value='{$row['ilce']}'>{$row['ilce']}</option>";
  }
}
?>
