<?php
$conn = new mysqli("localhost", "root", "", "tric");

$id = $_POST['id'];
$plate = $_POST['plate_number'];
$address = $_POST['address'];
$profile = $_POST['profile'];

$conn->query("UPDATE rider 
              SET plate_number='$plate', address='$address', profile='$profile' 
              WHERE riderId=$id");

echo "<script>
        alert('Rider updated successfully!');
        window.location='admin.php';
      </script>";
?>
