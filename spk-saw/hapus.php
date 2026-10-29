<?php
include 'koneksi.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    mysqli_query($koneksi, "DELETE FROM mahasiswa WHERE id='$id'");
}

header("Location: index.php");
exit();
?>