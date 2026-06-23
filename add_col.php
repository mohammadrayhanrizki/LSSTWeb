<?php
require 'koneksi.php';

$sql = "ALTER TABLE users ADD COLUMN last_activity DATETIME NULL DEFAULT NULL;";
if (mysqli_query($conn, $sql)) {
    echo "Column last_activity added successfully.";
} else {
    echo "Error adding column: " . mysqli_error($conn);
}
?>
