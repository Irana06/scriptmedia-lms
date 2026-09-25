<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rapor {{ $student->name }}</title>
    <style>
        @page { margin: 34px 42px 42px; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.45; }
        .kop { border-bottom: 3px double #0B2545; padding-bottom: 10px; width: 100%; }
        .kop td { vertical-align: middle; }
        .kop .logo { width: 72px; }
        .kop .logo img { height: 64px; width: 64px; }
        .kop .text { text-align: center; }
        .brand { color: #0B2545; font-size: 19px; font-weight: bold; letter-spacing: .4px; text-transform: uppercase; }
        .subtitle { color: #526070; font-size: 9.5px; margin-top: 3px; }
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
    @php($logo = $school->logoDataUri())
    <table class="kop">
        <tr>
            <td class="logo">@if($logo)<img src="{{ $logo }}" alt="Logo">@endif</td>
            <td class="text">
                <div class="brand">{{ $school->brandName() }}</div>
                @if($school->address || $school->city)<div class="subtitle">{{ collect([$school->address, $school->city])->filter()->implode(', ') }}</div>@endif
                @if($school->phone || $school->email || $school->npsn)<div class="subtitle">{{ collect([$school->npsn ? 'NPSN '.$school->npsn : null, $school->phone ? 'Telp. '.$school->phone : null, $school->email])->filter()->implode(' · ') }}</div>@endif
            </td>
            <td class="logo"></td>
        </tr>
    </table>
    <div class="title">RAPOR SEMESTER {{ strtoupper($semester->name) }}</div>
    <table class="identity"><tr><td class="label">Nama</td><td>: <strong>{{ $student->name }}</strong></td><td class="label">Kelas</td><td>: {{ $schoolClass->name }}</td></tr><tr><td class="label">{{ $student->nisn ? 'NISN' : 'NIS' }}</td><td>: {{ $student->nisn ?: $student->nis }}</td><td class="label">Tahun Ajaran</td><td>: {{ $schoolClass->academicYear->year_label }}</td></tr><tr><td class="label">Semester</td><td>: {{ $semester->name }}</td><td class="label">Wali Kelas</td><td>: {{ $schoolClass->homeroomTeacher?->name ?? '-' }}</td></tr></table>
    <div class="section">A. Hasil Belajar</div>
    <table class="report"><thead><tr><th style="width:28px" class="center">No.</th><th>Mata Pelajaran</th><th>Guru</th><th style="width:62px" class="center">Nilai</th><th style="width:60px" class="center">Predikat</th></tr></thead><tbody>@forelse($grades as $grade)<tr><td class="center">{{ $loop->iteration }}</td><td>{{ $grade->classSubject->subject->name }}</td><td>{{ $grade->classSubject->teacher->name }}</td><td class="center"><strong>{{ number_format((float)$grade->final_score, 2) }}</strong></td><td class="center"><strong>{{ $grade->predikat }}</strong></td></tr>@empty<tr><td colspan="5" class="center">Nilai belum tersedia.</td></tr>@endforelse</tbody></table>
    <div class="section">B. Rekap Kehadiran</div>
    <table class="attendance">@foreach(['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Tanpa keterangan'] as $status => $label)<tr><td>{{ $label }}</td><td class="value">{{ $attendanceCounts[$status] }} hari</td></tr>@endforeach</table>
    <table class="signatures">
        <tr>
            <td>&nbsp;<br>Orang Tua/Wali<div class="line">&nbsp;</div></td>
            <td>{{ $school->city ? $school->city.', ' : '' }}{{ now()->locale('id')->translatedFormat('d F Y') }}<br>Wali Kelas<div class="line">{{ $schoolClass->homeroomTeacher?->name ?? '________________' }}</div>@if($schoolClass->homeroomTeacher?->nip)<div>NIP {{ $schoolClass->homeroomTeacher->nip }}</div>@endif</td>
        </tr>
        @if($school->principal_name)
            <tr>
                <td colspan="2" style="padding-top: 26px;">Mengetahui,<br>Kepala Sekolah<div class="line">{{ $school->principal_name }}</div>@if($school->principal_nip)<div>NIP {{ $school->principal_nip }}</div>@endif</td>
            </tr>
        @endif
    </table>
    <div class="footer">Dicetak dari RuangKelas pada {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
