<?php
$conn = new mysqli("localhost", "root", "", "tric");

$id = $_GET['id'];

$conn->query("DELETE FROM rider WHERE riderId = $id");

echo "<script>
        alert('Rider deleted successfully!');
        window.location='admin.php';
      </script>";
?>
