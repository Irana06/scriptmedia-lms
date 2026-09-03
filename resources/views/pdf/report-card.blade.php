<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rapor {{ $student->name }}</title>
    <style>
        @page { margin: 34px 42px 42px; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.45; }
        .header { border-bottom: 3px solid #2CA6A4; padding-bottom: 15px; text-align: center; }
        .brand { color: #0B2545; font-size: 22px; font-weight: bold; letter-spacing: .5px; }
        .subtitle { color: #526070; font-size: 10px; margin-top: 4px; text-transform: uppercase; letter-spacing: 1.2px; }
        .title { color: #0B2545; font-size: 16px; font-weight: bold; margin: 20px 0 12px; text-align: center; }
        .identity { background: #F4FAFA; border: 1px solid #DDE8E8; border-radius: 8px; margin-bottom: 18px; padding: 12px 14px; width: 100%; }
        .identity td { padding: 3px 4px; vertical-align: top; }
        .label { color: #647382; width: 105px; }
        table.report { border-collapse: collapse; margin-top: 8px; width: 100%; }
        .report th { background: #0B2545; color: white; font-size: 10px; padding: 9px 8px; text-align: left; }
        .report td { border-bottom: 1px solid #DDE8E8; padding: 9px 8px; }
        .center { text-align: center !important; }
        .section { color: #0B2545; font-size: 13px; font-weight: bold; margin-top: 18px; }
        .attendance { margin-top: 8px; width: 55%; }
        .attendance td { border: 1px solid #DDE8E8; padding: 7px 9px; }
        .attendance .value { font-weight: bold; text-align: center; width: 60px; }
        .signatures { margin-top: 48px; width: 100%; }
        .signatures td { text-align: center; width: 50%; }
        .line { border-bottom: 1px solid #172033; display: inline-block; margin-top: 52px; min-width: 180px; padding-bottom: 3px; }
        .footer { bottom: -22px; color: #86939E; font-size: 8px; left: 0; position: fixed; right: 0; text-align: center; }
    </style>
</head>
<body>
    <div class="header"><div class="brand">RUANGKELAS</div><div class="subtitle">Laporan Hasil Belajar Siswa</div></div>
    <div class="title">RAPOR SEMESTER {{ strtoupper($semester->name) }}</div>
    <table class="identity"><tr><td class="label">Nama</td><td>: <strong>{{ $student->name }}</strong></td><td class="label">Kelas</td><td>: {{ $schoolClass->name }}</td></tr><tr><td class="label">NISN</td><td>: {{ $student->nisn }}</td><td class="label">Tahun Ajaran</td><td>: {{ $schoolClass->academicYear->year_label }}</td></tr><tr><td class="label">Semester</td><td>: {{ $semester->name }}</td><td class="label">Wali Kelas</td><td>: {{ $schoolClass->homeroomTeacher?->name ?? '-' }}</td></tr></table>
    <div class="section">A. Hasil Belajar</div>
    <table class="report"><thead><tr><th style="width:28px" class="center">No.</th><th>Mata Pelajaran</th><th>Guru</th><th style="width:62px" class="center">Nilai</th><th style="width:60px" class="center">Predikat</th></tr></thead><tbody>@forelse($grades as $grade)<tr><td class="center">{{ $loop->iteration }}</td><td>{{ $grade->classSubject->subject->name }}</td><td>{{ $grade->classSubject->teacher->name }}</td><td class="center"><strong>{{ number_format((float)$grade->final_score, 2) }}</strong></td><td class="center"><strong>{{ $grade->predikat }}</strong></td></tr>@empty<tr><td colspan="5" class="center">Nilai belum tersedia.</td></tr>@endforelse</tbody></table>
    <div class="section">B. Rekap Kehadiran</div>
    <table class="attendance">@foreach(['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Tanpa keterangan'] as $status => $label)<tr><td>{{ $label }}</td><td class="value">{{ $attendanceCounts[$status] }} hari</td></tr>@endforeach</table>
    <table class="signatures"><tr><td>Orang Tua/Wali<div class="line">&nbsp;</div></td><td>Wali Kelas<div class="line">{{ $schoolClass->homeroomTeacher?->name ?? '________________' }}</div></td></tr></table>
    <div class="footer">Dicetak dari RuangKelas pada {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
