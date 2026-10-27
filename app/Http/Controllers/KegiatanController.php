<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class KegiatanController extends Controller
{
    public function index()
    {
        $daftar = [];
        $error = null;

        try {
            $pdo = DB::connection()->getPdo();

            $stmt = $pdo->prepare(
                'SELECT k.id, k.kode_kegiatan, k.nama_kegiatan, k.email_pic,
                        k.kuota, j.nama_jenis
                 FROM kegiatan k
                 JOIN jenis_kegiatan j ON j.id = k.jenis_kegiatan_id
                 ORDER BY k.id ASC'
            );
            $stmt->execute();
            $daftar = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            report($e);
            $error = 'Data belum dapat ditampilkan. Silakan coba lagi nanti.';
        }

        return view('kegiatan.index', [
            'daftar' => $daftar,
            'error'  => $error,
        ]);
    }
}