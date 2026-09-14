<?php
require_once __DIR__ . '/../config/config.php';

class Model {
    public $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    // ---------- MASTER DATA ----------
    // Guru
    public function getAllGuru() {
        $q = "SELECT * FROM guru ORDER BY nama";
        $r = mysqli_query($this->conn, $q);
        $data = [];
        while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
        return $data;
    }
    public function addGuru($nama) {
        $nama = mysqli_real_escape_string($this->conn, $nama);
        $q = "INSERT INTO guru (nama) VALUES ('$nama')";
        return mysqli_query($this->conn, $q);
    }

    // Mapel
    public function getAllMapel() {
        $q = "SELECT m.*, g.nama as guru_nama 
              FROM mapel m 
              JOIN guru g ON m.guru_id = g.id 
              ORDER BY m.nama";
        $r = mysqli_query($this->conn, $q);
        $data = [];
        while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
        return $data;
    }
    public function addMapel($nama, $guru_id) {
        $nama = mysqli_real_escape_string($this->conn, $nama);
        $guru_id = intval($guru_id);
        $q = "INSERT INTO mapel (nama, guru_id) VALUES ('$nama', $guru_id)";
        return mysqli_query($this->conn, $q);
    }

    // Kelas
    public function getAllKelas() {
        $q = "SELECT * FROM kelas ORDER BY kelas";
        $r = mysqli_query($this->conn, $q);
        $data = [];
        while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
        return $data;
    }
    public function addKelas($nama) {
        $nama = mysqli_real_escape_string($this->conn, $nama);
        $q = "INSERT INTO kelas (kelas) VALUES ('$nama')";
        return mysqli_query($this->conn, $q);
    }

    // Siswa
    public function getSiswaByKelas($kelas_id) {
        $kelas_id = intval($kelas_id);
        // Tambahkan GROUP BY untuk menghindari duplikat
        $q = "SELECT s.*, k.nama as kelas_nama 
              FROM siswa s 
              JOIN kelas k ON s.kelas_id = k.id 
              WHERE s.kelas_id = $kelas_id 
              GROUP BY s.id 
              ORDER BY s.nama";
        $r = mysqli_query($this->conn, $q);
        $data = [];
        while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
        return $data;
    }
    public function getAllSiswa() {
        $q = "SELECT s.*, k.nama as kelas_nama 
              FROM siswa s 
              JOIN kelas k ON s.kelas_id = k.id 
              ORDER BY s.nama";
        $r = mysqli_query($this->conn, $q);
        $data = [];
        while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
        return $data;
    }
    public function addSiswa($nama, $kelas_id) {
        $nama = mysqli_real_escape_string($this->conn, $nama);
        $kelas_id = intval($kelas_id);
        $q = "INSERT INTO siswa (nama, kelas_id) VALUES ('$nama', $kelas_id)";
        return mysqli_query($this->conn, $q);
    }

    // ---------- KEHADIRAN ----------
    public function getKehadiranBySiswaMapelTanggal($siswa_id, $mapel_id, $tanggal) {
        $siswa_id = intval($siswa_id);
        $mapel_id = intval($mapel_id);
        $tanggal = mysqli_real_escape_string($this->conn, $tanggal);
        $q = "SELECT * FROM kehadiran WHERE siswa_id=$siswa_id AND mapel_id=$mapel_id AND tanggal='$tanggal'";
        $r = mysqli_query($this->conn, $q);
        return mysqli_fetch_assoc($r);
    }

    public function saveKehadiran($siswa_id, $mapel_id, $tanggal, $status, $alasan) {
        $siswa_id = intval($siswa_id);
        $mapel_id = intval($mapel_id);
        $tanggal = mysqli_real_escape_string($this->conn, $tanggal);
        $status = mysqli_real_escape_string($this->conn, $status);
        $alasan = mysqli_real_escape_string($this->conn, $alasan);
        $existing = $this->getKehadiranBySiswaMapelTanggal($siswa_id, $mapel_id, $tanggal);
        if ($existing) {
            $q = "UPDATE kehadiran SET status='$status', alasan='$alasan' WHERE id={$existing['id']}";
        } else {
            $q = "INSERT INTO kehadiran (siswa_id, mapel_id, tanggal, status, alasan) 
                  VALUES ($siswa_id, $mapel_id, '$tanggal', '$status', '$alasan')";
        }
        return mysqli_query($this->conn, $q);
    }

    public function getRekapKehadiran($kelas_id = null, $guru_id = null, $mapel_id = null, $tahun = null, $bulan = null) {
        $where = [];
        if ($kelas_id) $where[] = "k.id = " . intval($kelas_id);
        if ($guru_id) $where[] = "g.id = " . intval($guru_id);
        if ($mapel_id) $where[] = "m.id = " . intval($mapel_id);
        if ($tahun) $where[] = "YEAR(h.tanggal) = " . intval($tahun);
        if ($bulan) $where[] = "MONTH(h.tanggal) = " . intval($bulan);

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        $q = "SELECT 
                s.id as siswa_id, s.nama as siswa_nama, 
                k.id as kelas_id, k.nama as kelas_nama,
                m.id as mapel_id, m.nama as mapel_nama,
                g.id as guru_id, g.nama as guru_nama,
                h.tanggal, h.status, h.alasan
              FROM kehadiran h
              JOIN siswa s ON h.siswa_id = s.id
              JOIN kelas k ON s.kelas_id = k.id
              JOIN mapel m ON h.mapel_id = m.id
              JOIN guru g ON m.guru_id = g.id
              $whereClause
              ORDER BY h.tanggal, s.nama";
        $r = mysqli_query($this->conn, $q);
        $data = [];
        while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
        return $data;
    }

    public function getSiswaByKelasForAbsen($kelas_id) {
        return $this->getSiswaByKelas($kelas_id);
    }

    public function getDaysInMonth($bulan, $tahun) {
        return cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
    }
}
?>