<?php
include 'koneksi.php';

// --- 1. PROSES TAMBAH DATA ---
if (isset($_POST['tambah_data'])) {
    $npm  = $_POST['npm'];
    $nama = $_POST['nama'];
    $c1   = $_POST['c1'];
    $c2   = $_POST['c2'];
    $c3   = $_POST['c3'];
    $c4   = $_POST['c4'];
    $c5   = $_POST['c5'];

    $query = "INSERT INTO mahasiswa (npm, nama, c1_penghasilan, c2_tanggungan, c3_ipk, c4_kehadiran, c5_attitude) 
              VALUES ('$npm', '$nama', '$c1', '$c2', '$c3', '$c4', '$c5')";
    mysqli_query($koneksi, $query);
    header("Location: index.php");
    exit();
}

// --- 2. PROSES UPDATE / EDIT DATA ---
if (isset($_POST['update_data'])) {
    $id   = $_POST['id'];
    $npm  = $_POST['npm'];
    $nama = $_POST['nama'];
    $c1   = $_POST['c1'];
    $c2   = $_POST['c2'];
    $c3   = $_POST['c3'];
    $c4   = $_POST['c4'];
    $c5   = $_POST['c5'];

    $query = "UPDATE mahasiswa SET 
                npm='$npm', 
                nama='$nama', 
                c1_penghasilan='$c1', 
                c2_tanggungan='$c2', 
                c3_ipk='$c3', 
                c4_kehadiran='$c4', 
                c5_attitude='$c5' 
              WHERE id='$id'";
    mysqli_query($koneksi, $query);
    header("Location: index.php");
    exit();
}

// --- AMBIL DATA UNTUK FORM EDIT JIKA TOMBOL EDIT DIKLIK ---
$edit_data = null;
if (isset($_GET['edit'])) {
    $id_edit = $_GET['edit'];
    $res_edit = mysqli_query($koneksi, "SELECT * FROM mahasiswa WHERE id='$id_edit'");
    $edit_data = mysqli_fetch_assoc($res_edit);
}

// --- AMBIL SEMUA DATA MAHASISWA ---
$result = mysqli_query($koneksi, "SELECT * FROM mahasiswa ORDER BY id DESC");
$data_mhs = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data_mhs[] = $row;
}

// --- BOBOT KRITERIA (W) ---
$w = [
    'c1' => 0.20, // Cost
    'c2' => 0.20, // Benefit
    'c3' => 0.20, // Benefit
    'c4' => 0.20, // Benefit
    'c5' => 0.20  // Benefit
];

// --- HITUNG MIN/MAX UNTUK NORMALISASI SAW ---
$min_c1 = PHP_INT_MAX;
$max_c2 = 0; $max_c3 = 0; $max_c4 = 0; $max_c5 = 0;

foreach ($data_mhs as $m) {
    if ($m['c1_penghasilan'] < $min_c1) $min_c1 = $m['c1_penghasilan'];
    if ($m['c2_tanggungan'] > $max_c2)  $max_c2 = $m['c2_tanggungan'];
    if ($m['c3_ipk'] > $max_c3)         $max_c3 = $m['c3_ipk'];
    if ($m['c4_kehadiran'] > $max_c4)   $max_c4 = $m['c4_kehadiran'];
    if ($m['c5_attitude'] > $max_c5)    $max_c5 = $m['c5_attitude'];
}

// --- PERHITUNGAN PREFERENSI (V) ---
$ranking = [];
foreach ($data_mhs as $m) {
    $r1 = ($min_c1 != 0 && $m['c1_penghasilan'] != 0) ? ($min_c1 / $m['c1_penghasilan']) : 0;
    $r2 = ($max_c2 != 0) ? ($m['c2_tanggungan'] / $max_c2) : 0;
    $r3 = ($max_c3 != 0) ? ($m['c3_ipk'] / $max_c3) : 0;
    $r4 = ($max_c4 != 0) ? ($m['c4_kehadiran'] / $max_c4) : 0;
    $r5 = ($max_c5 != 0) ? ($m['c5_attitude'] / $max_c5) : 0;

    $v = ($r1 * $w['c1']) + ($r2 * $w['c2']) + ($r3 * $w['c3']) + ($r4 * $w['c4']) + ($r5 * $w['c5']);

    $ranking[] = [
        'npm' => $m['npm'],
        'nama' => $m['nama'],
        'v' => $v
    ];
}

usort($ranking, function($a, $b) {
    return $b['v'] <=> $a['v'];
});
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SPK Metode SAW - Calon Beasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: sans-serif; }
        .card { border-radius: 10px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .table-blue th { background-color: #007bff !important; color: white; text-align: center; }
        .rank-top { background-color: #d1e7dd !important; font-weight: bold; }
    </style>
</head>
<body class="p-4">

<div class="container">

    <!-- FORM TAMBAH / EDIT DATA -->
    <div class="card p-4 mb-4">
        <h5 class="fw-bold mb-3">
            <?= $edit_data ? 'Edit Data Mahasiswa Calon Beasiswa' : 'Tambah Mahasiswa Calon Beasiswa' ?>
        </h5>
        <form method="POST" action="">
            <!-- Jika sedang mode edit, kirimkan ID tersembunyi -->
            <?php if ($edit_data): ?>
                <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
            <?php endif; ?>

            <div class="row g-2 mb-2">
                <div class="col-md-6">
                    <input type="text" name="npm" class="form-control" placeholder="NPM" value="<?= $edit_data['npm'] ?? '' ?>" required>
                </div>
                <div class="col-md-6">
                    <input type="text" name="nama" class="form-control" placeholder="Nama Mahasiswa" value="<?= $edit_data['nama'] ?? '' ?>" required>
                </div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col">
                    <input type="number" step="0.01" name="c1" class="form-control" placeholder="C1 (Penghasilan)" value="<?= $edit_data['c1_penghasilan'] ?? '' ?>" required>
                </div>
                <div class="col">
                    <input type="number" name="c2" class="form-control" placeholder="C2 (Tanggungan)" value="<?= $edit_data['c2_tanggungan'] ?? '' ?>" required>
                </div>
                <div class="col">
                    <input type="number" step="0.01" name="c3" class="form-control" placeholder="C3 (IPK)" value="<?= $edit_data['c3_ipk'] ?? '' ?>" required>
                </div>
                <div class="col">
                    <input type="number" step="0.01" name="c4" class="form-control" placeholder="C4 (Kehadiran)" value="<?= $edit_data['c4_kehadiran'] ?? '' ?>" required>
                </div>
                <div class="col">
                    <input type="number" step="0.01" name="c5" class="form-control" placeholder="C5 (Attitude)" value="<?= $edit_data['c5_attitude'] ?? '' ?>" required>
                </div>
            </div>

            <?php if ($edit_data): ?>
                <button type="submit" name="update_data" class="btn btn-warning btn-sm px-3 text-white fw-bold">Update Data</button>
                <a href="index.php" class="btn btn-secondary btn-sm px-3">Batal</a>
            <?php else: ?>
                <button type="submit" name="tambah_data" class="btn btn-success btn-sm px-3">Tambah Data</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- DATA MASTER MAHASISWA (ALTERNATIF) -->
    <div class="card p-4 mb-4">
        <h5 class="fw-bold mb-3">Data Master Mahasiswa (Alternatif)</h5>
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center">
                <thead class="table-blue">
                    <tr>
                        <th>NPM</th>
                        <th>Nama</th>
                        <th>C1 (Cost)</th>
                        <th>C2 (Benefit)</th>
                        <th>C3 (Benefit)</th>
                        <th>C4 (Benefit)</th>
                        <th>C5 (Benefit)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($data_mhs) > 0): ?>
                        <?php foreach ($data_mhs as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['npm']) ?></td>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= number_format($row['c1_penghasilan'], 2, '.', '') ?></td>
                            <td><?= number_format($row['c2_tanggungan'], 2, '.', '') ?></td>
                            <td><?= number_format($row['c3_ipk'], 2, '.', '') ?></td>
                            <td><?= number_format($row['c4_kehadiran'], 2, '.', '') ?></td>
                            <td><?= number_format($row['c5_attitude'], 2, '.', '') ?></td>
                            <td>
                                <a href="index.php?edit=<?= $row['id'] ?>" class="btn btn-warning btn-sm text-white">Edit</a>
                                <a href="hapus.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8">Belum ada data mahasiswa.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- HASIL PERHITUNGAN & RANKING SPK SAW -->
    <div class="card p-4 mb-4">
        <h5 class="fw-bold mb-3">Hasil Perhitungan & Ranking SPK SAW</h5>
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center">
                <thead class="table-blue">
                    <tr>
                        <th>Peringkat</th>
                        <th>NPM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Nilai Preferensi (V)</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($ranking as $rank): 
                        $is_top = ($no === 1);
                    ?>
                    <tr class="<?= $is_top ? 'rank-top' : '' ?>">
                        <td><?= $no ?></td>
                        <td><?= htmlspecialchars($rank['npm']) ?></td>
                        <td><?= htmlspecialchars($rank['nama']) ?></td>
                        <td><?= number_format($rank['v'], 4, '.', '') ?></td>
                        <td>
                            <?php if ($is_top): ?>
                                🏆 <strong>Terpilih (Penerima Utama)</strong>
                            <?php else: ?>
                                Dipertimbangkan
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                    $no++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>