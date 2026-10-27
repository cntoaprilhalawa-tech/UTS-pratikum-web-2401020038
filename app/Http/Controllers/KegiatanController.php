<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class KegiatanController extends Controller
{
    public function index(Request $request)
    {
        $daftar = [];
        $jenis = [];
        $error = null;

        $q = trim(strip_tags((string) $request->query('q', '')));
        $filter = trim((string) $request->query('jenis_kegiatan_id', ''));
        if (!ctype_digit($filter)) {
            $filter = '';
        }

        try {
            $pdo = DB::connection()->getPdo();

            $sql = 'SELECT k.id, k.kode_kegiatan, k.nama_kegiatan, k.email_pic,
                           k.kuota, j.nama_jenis
                    FROM kegiatan k
                    JOIN jenis_kegiatan j ON j.id = k.jenis_kegiatan_id';
            $kondisi = [];
            $param = [];

            if ($q !== '') {
                $kondisi[] = '(k.kode_kegiatan LIKE :q1
                               OR k.nama_kegiatan LIKE :q2
                               OR k.email_pic LIKE :q3)';
                $like = '%' . $q . '%';
                $param[':q1'] = $like;
                $param[':q2'] = $like;
                $param[':q3'] = $like;
            }

            if ($filter !== '') {
                $kondisi[] = 'k.jenis_kegiatan_id = :jenis';
                $param[':jenis'] = $filter;
            }

            if ($kondisi) {
                $sql .= ' WHERE ' . implode(' AND ', $kondisi);
            }
            $sql .= ' ORDER BY k.id ASC';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($param);
            $daftar = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmtJenis = $pdo->prepare('SELECT id, nama_jenis FROM jenis_kegiatan ORDER BY nama_jenis ASC');
            $stmtJenis->execute();
            $jenis = $stmtJenis->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            report($e);
            $error = 'Data belum dapat ditampilkan. Silakan coba lagi nanti.';
        }

        return view('kegiatan.index', [
            'daftar' => $daftar,
            'jenis'  => $jenis,
            'error'  => $error,
            'q'      => $q,
            'filter' => $filter,
        ]);
    }

    public function store(Request $request)
    {
        // Sanitasi: trim dan strip_tags
        $request->merge([
            'kode_kegiatan' => trim(strip_tags((string) $request->input('kode_kegiatan'))),
            'nama_kegiatan' => trim(strip_tags((string) $request->input('nama_kegiatan'))),
            'email_pic'     => trim(strip_tags((string) $request->input('email_pic'))),
            'kuota'         => trim(strip_tags((string) $request->input('kuota'))),
        ]);

        // Validasi server-side (gagal otomatis kembali ke form dengan error dan old())
        $data = $request->validate([
            'kode_kegiatan'     => ['required', 'regex:/^[A-Za-z0-9]{10}$/'],
            'nama_kegiatan'     => ['required', 'string', 'min:3', 'max:100'],
            'email_pic'         => ['required', 'email', 'max:100'],
            'kuota'             => ['required', 'integer', 'between:10,200'],
            'jenis_kegiatan_id' => ['required', 'integer'],
        ], [
            'required'            => ':attribute wajib diisi.',
            'kode_kegiatan.regex' => 'Kode kegiatan harus tepat 10 karakter alfanumerik.',
            'min'                 => ':attribute minimal :min karakter.',
            'max'                 => ':attribute maksimal :max karakter.',
            'email'               => 'Format email tidak valid.',
            'integer'             => ':attribute harus berupa bilangan bulat.',
            'between'             => ':attribute harus antara :min sampai :max.',
        ], [
            'kode_kegiatan'     => 'Kode kegiatan',
            'nama_kegiatan'     => 'Nama kegiatan',
            'email_pic'         => 'Email PIC',
            'kuota'             => 'Kuota',
            'jenis_kegiatan_id' => 'Jenis kegiatan',
        ]);

        try {
            $pdo = DB::connection()->getPdo();

            // Jenis harus tersedia
            $cek = $pdo->prepare('SELECT COUNT(*) FROM jenis_kegiatan WHERE id = :id');
            $cek->execute([':id' => $data['jenis_kegiatan_id']]);
            if ((int) $cek->fetchColumn() === 0) {
                return back()->withErrors(['jenis_kegiatan_id' => 'Jenis kegiatan tidak tersedia.'])->withInput();
            }

            // Cek duplikat kode dan email
            $errors = [];

            $cekKode = $pdo->prepare('SELECT COUNT(*) FROM kegiatan WHERE kode_kegiatan = :kode');
            $cekKode->execute([':kode' => $data['kode_kegiatan']]);
            if ((int) $cekKode->fetchColumn() > 0) {
                $errors['kode_kegiatan'] = 'Kode kegiatan sudah digunakan.';
            }

            $cekEmail = $pdo->prepare('SELECT COUNT(*) FROM kegiatan WHERE email_pic = :email');
            $cekEmail->execute([':email' => $data['email_pic']]);
            if ((int) $cekEmail->fetchColumn() > 0) {
                $errors['email_pic'] = 'Email PIC sudah digunakan.';
            }

            if ($errors) {
                return back()->withErrors($errors)->withInput();
            }

            // Simpan
            $insert = $pdo->prepare(
                'INSERT INTO kegiatan
                    (kode_kegiatan, nama_kegiatan, email_pic, kuota, jenis_kegiatan_id, created_at, updated_at)
                 VALUES
                    (:kode, :nama, :email, :kuota, :jenis, NOW(), NOW())'
            );
            $insert->execute([
                ':kode'  => $data['kode_kegiatan'],
                ':nama'  => $data['nama_kegiatan'],
                ':email' => $data['email_pic'],
                ':kuota' => $data['kuota'],
                ':jenis' => $data['jenis_kegiatan_id'],
            ]);
        } catch (Throwable $e) {
            report($e);

            if ($e->getCode() === '23000') {
                return back()->withErrors(['kode_kegiatan' => 'Kode atau email sudah digunakan.'])->withInput();
            }

            return back()->withErrors(['umum' => 'Data gagal disimpan. Silakan coba lagi nanti.'])->withInput();
        }

        return redirect()->route('kegiatan.index')->with('sukses', 'Kegiatan berhasil ditambahkan.');
    }

    public function edit($id)
    {
        try {
            $pdo = DB::connection()->getPdo();

            $stmt = $pdo->prepare(
                'SELECT id, kode_kegiatan, nama_kegiatan, email_pic, kuota, jenis_kegiatan_id
                 FROM kegiatan WHERE id = :id'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmtJenis = $pdo->prepare('SELECT id, nama_jenis FROM jenis_kegiatan ORDER BY nama_jenis ASC');
            $stmtJenis->execute();
            $jenis = $stmtJenis->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('kegiatan.index')
                ->withErrors(['umum' => 'Data belum dapat ditampilkan. Silakan coba lagi nanti.']);
        }

        if (!$row) {
            return redirect()->route('kegiatan.index')
                ->withErrors(['umum' => 'Data kegiatan tidak ditemukan.']);
        }

        return view('kegiatan.edit', ['row' => $row, 'jenis' => $jenis]);
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'kode_kegiatan' => trim(strip_tags((string) $request->input('kode_kegiatan'))),
            'nama_kegiatan' => trim(strip_tags((string) $request->input('nama_kegiatan'))),
            'email_pic'     => trim(strip_tags((string) $request->input('email_pic'))),
            'kuota'         => trim(strip_tags((string) $request->input('kuota'))),
        ]);

        $data = $request->validate([
            'kode_kegiatan'     => ['required', 'regex:/^[A-Za-z0-9]{10}$/'],
            'nama_kegiatan'     => ['required', 'string', 'min:3', 'max:100'],
            'email_pic'         => ['required', 'email', 'max:100'],
            'kuota'             => ['required', 'integer', 'between:10,200'],
            'jenis_kegiatan_id' => ['required', 'integer'],
        ], [
            'required'            => ':attribute wajib diisi.',
            'kode_kegiatan.regex' => 'Kode kegiatan harus tepat 10 karakter alfanumerik.',
            'min'                 => ':attribute minimal :min karakter.',
            'max'                 => ':attribute maksimal :max karakter.',
            'email'               => 'Format email tidak valid.',
            'integer'             => ':attribute harus berupa bilangan bulat.',
            'between'             => ':attribute harus antara :min sampai :max.',
        ], [
            'kode_kegiatan'     => 'Kode kegiatan',
            'nama_kegiatan'     => 'Nama kegiatan',
            'email_pic'         => 'Email PIC',
            'kuota'             => 'Kuota',
            'jenis_kegiatan_id' => 'Jenis kegiatan',
        ]);

        try {
            $pdo = DB::connection()->getPdo();

            $ada = $pdo->prepare('SELECT COUNT(*) FROM kegiatan WHERE id = :id');
            $ada->execute([':id' => $id]);
            if ((int) $ada->fetchColumn() === 0) {
                return redirect()->route('kegiatan.index')
                    ->withErrors(['umum' => 'Data kegiatan tidak ditemukan.']);
            }

            $cek = $pdo->prepare('SELECT COUNT(*) FROM jenis_kegiatan WHERE id = :id');
            $cek->execute([':id' => $data['jenis_kegiatan_id']]);
            if ((int) $cek->fetchColumn() === 0) {
                return back()->withErrors(['jenis_kegiatan_id' => 'Jenis kegiatan tidak tersedia.'])->withInput();
            }

            // Cek unik milik data lain (data sendiri dikecualikan)
            $errors = [];

            $cekKode = $pdo->prepare('SELECT COUNT(*) FROM kegiatan WHERE kode_kegiatan = :kode AND id <> :id');
            $cekKode->execute([':kode' => $data['kode_kegiatan'], ':id' => $id]);
            if ((int) $cekKode->fetchColumn() > 0) {
                $errors['kode_kegiatan'] = 'Kode kegiatan sudah digunakan kegiatan lain.';
            }

            $cekEmail = $pdo->prepare('SELECT COUNT(*) FROM kegiatan WHERE email_pic = :email AND id <> :id');
            $cekEmail->execute([':email' => $data['email_pic'], ':id' => $id]);
            if ((int) $cekEmail->fetchColumn() > 0) {
                $errors['email_pic'] = 'Email PIC sudah digunakan kegiatan lain.';
            }

            if ($errors) {
                return back()->withErrors($errors)->withInput();
            }

            $upd = $pdo->prepare(
                'UPDATE kegiatan
                 SET kode_kegiatan = :kode, nama_kegiatan = :nama, email_pic = :email,
                     kuota = :kuota, jenis_kegiatan_id = :jenis, updated_at = NOW()
                 WHERE id = :id'
            );
            $upd->execute([
                ':kode'  => $data['kode_kegiatan'],
                ':nama'  => $data['nama_kegiatan'],
                ':email' => $data['email_pic'],
                ':kuota' => $data['kuota'],
                ':jenis' => $data['jenis_kegiatan_id'],
                ':id'    => $id,
            ]);
        } catch (Throwable $e) {
            report($e);

            if ($e->getCode() === '23000') {
                return back()->withErrors(['kode_kegiatan' => 'Kode atau email sudah digunakan.'])->withInput();
            }

            return back()->withErrors(['umum' => 'Data gagal diperbarui. Silakan coba lagi nanti.'])->withInput();
        }

        return redirect()->route('kegiatan.index')->with('sukses', 'Kegiatan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        try {
            $pdo = DB::connection()->getPdo();

            $del = $pdo->prepare('DELETE FROM kegiatan WHERE id = :id');
            $del->execute([':id' => $id]);

            if ($del->rowCount() === 0) {
                return redirect()->route('kegiatan.index')
                    ->withErrors(['umum' => 'Data kegiatan tidak ditemukan.']);
            }
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('kegiatan.index')
                ->withErrors(['umum' => 'Data gagal dihapus. Silakan coba lagi nanti.']);
        }

        return redirect()->route('kegiatan.index')->with('sukses', 'Kegiatan berhasil dihapus.');
    }
}