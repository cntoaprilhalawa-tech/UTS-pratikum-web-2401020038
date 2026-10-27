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

    @if (session('sukses'))
        <p>{{ session('sukses') }}</p>
    @endif

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    @endif

    <h2>Tambah Kegiatan</h2>
    <form method="POST" action="{{ route('kegiatan.store') }}">
        @csrf
        <p>
            <label>Kode Kegiatan</label><br>
            <input type="text" name="kode_kegiatan" value="{{ old('kode_kegiatan') }}">
        </p>
        <p>
            <label>Nama Kegiatan</label><br>
            <input type="text" name="nama_kegiatan" value="{{ old('nama_kegiatan') }}">
        </p>
        <p>
            <label>Email PIC</label><br>
            <input type="text" name="email_pic" value="{{ old('email_pic') }}">
        </p>
        <p>
            <label>Kuota</label><br>
            <input type="text" name="kuota" value="{{ old('kuota') }}">
        </p>
        <p>
            <label>Jenis Kegiatan</label><br>
            <select name="jenis_kegiatan_id">
                <option value="">-- Pilih jenis --</option>
                @foreach ($jenis as $j)
                    <option value="{{ $j['id'] }}" {{ (string) old('jenis_kegiatan_id') === (string) $j['id'] ? 'selected' : '' }}>
                        {{ $j['nama_jenis'] }}
                    </option>
                @endforeach
            </select>
        </p>
        <button type="submit">Simpan</button>
    </form>

    <br><hr><br>

    <!-- FORM CARI DAN FILTER DISISIPKAN DI SINI -->
    <h2>Cari dan Filter</h2>
    <form method="GET" action="{{ route('kegiatan.index') }}">
        <input type="text" name="q" value="{{ $q }}" placeholder="Kode, nama, atau email">
        <select name="jenis_kegiatan_id">
            <option value="">-- Semua jenis --</option>
            @foreach ($jenis as $j)
                <option value="{{ $j['id'] }}" {{ (string) $filter === (string) $j['id'] ? 'selected' : '' }}>
                    {{ $j['nama_jenis'] }}
                </option>
            @endforeach
        </select>
        <button type="submit">Cari</button>
        <a href="{{ route('kegiatan.index') }}">Reset</a>
    </form>

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
                <th>Aksi</th>
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
                    <td>
                        <a href="{{ route('kegiatan.edit', $row['id']) }}">Edit</a>
                        <form method="POST" action="{{ route('kegiatan.destroy', $row['id']) }}" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Belum ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>