<?php
require_once __DIR__ . '/../model/model.php';

class Controller {
    private $model;

    public function __construct() {
        $this->model = new Model();
    }

    public function run() {
        
        $kelas_id = isset($_GET['kelas']) ? intval($_GET['kelas']) : 0;
        $guru_id = isset($_GET['guru']) ? intval($_GET['guru']) : 0;
        $mapel_id = isset($_GET['mapel']) ? intval($_GET['mapel']) : 0;
        $tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
        $bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : date('n');

        // Parameter untuk form absen
        $kelas_absen = isset($_GET['kelas_absen']) ? intval($_GET['kelas_absen']) : 0;
        $mapel_absen = isset($_GET['mapel_absen']) ? intval($_GET['mapel_absen']) : 0;
        $tanggal_absen = isset($_GET['tanggal_absen']) ? $_GET['tanggal_absen'] : date('Y-m-d');

        
        $active_tab = 'rekap';
        if (isset($_GET['tab'])) {
            $active_tab = $_GET['tab'];
        } elseif ($kelas_absen > 0 || $mapel_absen > 0) {
            $active_tab = 'absen';
        }

        $master_message = '';

        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Tambah Guru
            if (isset($_POST['tambah_guru']) && !empty($_POST['nama_guru'])) {
                $nama = trim($_POST['nama_guru']);
                if ($this->model->addGuru($nama)) {
                    $master_message = "Guru berhasil ditambahkan!";
                } else {
                    $master_message = "Gagal tambah guru.";
                }
            }
            
            elseif (isset($_POST['tambah_mapel']) && !empty($_POST['nama_mapel']) && !empty($_POST['guru_id_mapel'])) {
                $nama = trim($_POST['nama_mapel']);
                $guru = intval($_POST['guru_id_mapel']);
                if ($this->model->addMapel($nama, $guru)) {
                    $master_message = "Mapel berhasil ditambahkan!";
                } else {
                    $master_message = "Gagal tambah mapel.";
                }
            }
            
            elseif (isset($_POST['tambah_kelas']) && !empty($_POST['nama_kelas'])) {
                $nama = trim($_POST['nama_kelas']);
                if ($this->model->addKelas($nama)) {
                    $master_message = "Kelas berhasil ditambahkan!";
                } else {
                    $master_message = "Gagal tambah kelas.";
                }
            }
            
            elseif (isset($_POST['tambah_siswa']) && !empty($_POST['nama_siswa']) && !empty($_POST['kelas_id_siswa'])) {
                $nama = trim($_POST['nama_siswa']);
                $kelas = intval($_POST['kelas_id_siswa']);
                if ($this->model->addSiswa($nama, $kelas)) {
                    // Redirect ke tab master dengan pesan sukses
                    $redirect = "index.php?tab=master&success=1&kelas_absen=$kelas_absen&mapel_absen=$mapel_absen&tanggal_absen=$tanggal_absen";
                    header("Location: $redirect");
                    exit;
                } else {
                    $master_message = "Gagal tambah siswa.";
                }
            }
            
            elseif (isset($_POST['simpan_kehadiran'])) {
                $mapel_id_post = intval($_POST['mapel_id_absen']);
                $tanggal = $_POST['tanggal_absen'];
                $siswa_ids = $_POST['siswa_id'] ?? [];
                $statuses = $_POST['status'] ?? [];
                $alasans = $_POST['alasan'] ?? [];

                $success_count = 0;
                foreach ($siswa_ids as $idx => $siswa_id) {
                    $status = $statuses[$idx] ?? 'masuk';
                    $alasan = ($status == 'masuk') ? '' : ($alasans[$idx] ?? '');
                    if ($this->model->saveKehadiran($siswa_id, $mapel_id_post, $tanggal, $status, $alasan)) {
                        $success_count++;
                    }
                }
                
                $redirect = "index.php?tab=absen&kelas_absen=$kelas_absen&mapel_absen=$mapel_absen&tanggal_absen=$tanggal_absen";
                if ($success_count > 0) {
                    $redirect .= "&success=1";
                }
                header("Location: $redirect");
                exit;
            }
        }

        
        $allKelas = $this->model->getAllKelas();
        $allGuru = $this->model->getAllGuru();
        $allMapel = $this->model->getAllMapel();

        
        $rekap = $this->model->getRekapKehadiran($kelas_id, $guru_id, $mapel_id, $tahun, $bulan);
        $matrix = [];
        $tanggal_list = [];
        foreach ($rekap as $row) {
            $key = $row['siswa_id'] . '|' . $row['siswa_nama'] . '|' . $row['kelas_nama'];
            $tgl = date('j', strtotime($row['tanggal']));
            $tanggal_list[] = $row['tanggal'];
            if (!isset($matrix[$key])) {
                $matrix[$key] = [
                    'siswa_id' => $row['siswa_id'],
                    'nama' => $row['siswa_nama'],
                    'kelas' => $row['kelas_nama'],
                    'status_per_tgl' => []
                ];
            }
            $matrix[$key]['status_per_tgl'][$tgl] = $row['status'];
        }
        $unique_tanggal = array_unique($tanggal_list);
        sort($unique_tanggal);
        $days = count($unique_tanggal);

        
        $daftar_siswa = [];
        if ($kelas_absen > 0 && $mapel_absen > 0) {
            $daftar_siswa = $this->model->getSiswaByKelasForAbsen($kelas_absen);
            foreach ($daftar_siswa as &$siswa) {
                $existing = $this->model->getKehadiranBySiswaMapelTanggal($siswa['id'], $mapel_absen, $tanggal_absen);
                if ($existing) {
                    $siswa['status_existing'] = $existing['status'];
                    $siswa['alasan_existing'] = $existing['alasan'];
                } else {
                    $siswa['status_existing'] = '';
                    $siswa['alasan_existing'] = '';
                }
            }
            unset($siswa); 
        }

        
        if (isset($_GET['success'])) {
            $master_message = "Operasi berhasil!";
        }

        
        include __DIR__ . '/../view/dashboard.php';
    }
}
?>