<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Kegiatan</title>
</head>
<body>
    <h1>Edit Kegiatan</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('kegiatan.update', $row['id']) }}">
        @csrf
        @method('PUT')
        <p>
            <label>Kode Kegiatan</label><br>
            <input type="text" name="kode_kegiatan" value="{{ old('kode_kegiatan', $row['kode_kegiatan']) }}">
        </p>
        <p>
            <label>Nama Kegiatan</label><br>
            <input type="text" name="nama_kegiatan" value="{{ old('nama_kegiatan', $row['nama_kegiatan']) }}">
        </p>
        <p>
            <label>Email PIC</label><br>
            <input type="text" name="email_pic" value="{{ old('email_pic', $row['email_pic']) }}">
        </p>
        <p>
            <label>Kuota</label><br>
            <input type="text" name="kuota" value="{{ old('kuota', $row['kuota']) }}">
        </p>
        <p>
            <label>Jenis Kegiatan</label><br>
            <select name="jenis_kegiatan_id">
                <option value="">-- Pilih jenis --</option>
                @foreach ($jenis as $j)
                    <option value="{{ $j['id'] }}"
                        {{ (string) old('jenis_kegiatan_id', $row['jenis_kegiatan_id']) === (string) $j['id'] ? 'selected' : '' }}>
                        {{ $j['nama_jenis'] }}
                    </option>
                @endforeach
            </select>
        </p>
        <button type="submit">Simpan Perubahan</button>
        <a href="{{ route('kegiatan.index') }}">Batal</a>
    </form>
</body>
</html>