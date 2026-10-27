<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Kegiatan Mahasiswa</title>
</head>
<body>
    <h1>Data Kegiatan Mahasiswa</h1>

    @if ($error)
        <p>{{ $error }}</p>
    @endif

    <h2>Daftar Kegiatan</h2>
    <table border="1" cellpadding="4" cellspacing="0">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Nama Kegiatan</th>
                <th>Email PIC</th>
                <th>Kuota</th>
                <th>Status Kuota</th>
                <th>Jenis</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($daftar as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['kode_kegiatan'] }}</td>
                    <td>{{ $row['nama_kegiatan'] }}</td>
                    <td>{{ $row['email_pic'] }}</td>
                    <td>{{ $row['kuota'] }}</td>
                    <td>
                        @if (statusKuota($row['kuota']) === 'Besar')
                            Besar
                        @else
                            Terbatas
                        @endif
                    </td>
                    <td>{{ $row['nama_jenis'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Belum ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>