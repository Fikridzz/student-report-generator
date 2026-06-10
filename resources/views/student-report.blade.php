<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Capaian Tahfidz</title>
    <style>
        @font-face {
            font-family: 'Amiri';
            font-style: normal;
            font-weight: normal;
            /* DomPDF requires absolute server paths to read local fonts correctly */
            src: url("{{ storage_path('fonts/Amiri-Regular.ttf') }}") format('truetype');
        }

        /* Base Styles */
        body {
            font-family: 'Amiri', 'Times New Roman', serif;
            /* Matching the document font */
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        /* Main Header */
        .page-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        /* Top Info Section */
        .info-table {
            width: 100%;
            margin-bottom: 25px;
            border-spacing: 0;
            border: none !important;
        }

        .info-table td {
            border: none !important;
            vertical-align: top;
            padding: 0 !important;
        }

        .info-sub-table {
            width: 100%;
            border-spacing: 0;
            border: none !important;
        }

        .info-sub-table td {
            border: none !important;
            font-size: 12px;
        }

        .info-sub-table .label-cell {
            width: 105px;
            white-space: nowrap;
        }

        .info-sub-table .colon-cell {
            width: 15px;
            text-align: left;
        }

        /* Main Grades Table */
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            text-align: center;
        }

        .grades-table th,
        .grades-table td {
            vertical-align: top;
            border: 1px solid #000;
            padding: 4px 0px;
        }

        .grades-table th {
            background-color: #095AB2;
            /* Blue header */
            color: white;
            font-weight: bold;
        }

        .grades-table .no-order {
            font-weight: bold;
        }

        .grades-table .surah-name {
            font-weight: bold;
        }

        .grades-table .grade-val {
            font-weight: bold;
        }

        .grades-table .keterangan-val {
            font-weight: bold;
        }

        .score-filled {
            background-color: #82C940 !important;
        }

        /* Bottom Section: Legend & Signatures */
        .bottom-layout {
            width: 100%;
            border-collapse: collapse;
        }

        .bottom-layout td {
            vertical-align: top;
        }

        /* Legend Table */
        .legend-title {
            font-style: italic;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .legend-table {
            border-collapse: collapse;
            width: 200px;
            text-align: left;
            margin-bottom: 15px;
        }

        .legend-table th,
        .legend-table td {
            border: 1px solid #000;
            padding: 3px 5px;
            font-size: 12px;
            font-weight: bold;
        }

        .legend-table th {
            background-color: #095AB2;
            color: white;
        }

        /* Catatan Section */
        .catatan-title {
            font-style: italic;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }

        .catatan-table {
            border-collapse: collapse;
            font-weight: bold;
        }

        .catatan-table td {
            padding: 2px 0;
        }

        /* Signature Area */
        .signature-area {
            display: flex;
            justify-content: flex-end;
            font-weight: bold;
            padding-top: 10px;
        }

        .signature-name {
            font-weight: bold;
            font-size: 12px;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="page-title">{{ $title }}</div>

    <table class="info-table">
        <tr>
            <td style="width: 50%; padding-right: 15px;">
                <table class="info-sub-table">
                    <tr>
                        <td class="label-cell">Nama</td>
                        <td class="colon-cell">:</td>
                        <td>{{ $student['name'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Kelas</td>
                        <td class="colon-cell">:</td>
                        <td>{{ $student['class'] ?? '' }}</td>
                    </tr>
                </table>
            </td>

            <td style="width: 50%; padding-left: 15px;">
                <table class="info-sub-table">
                    <tr>
                        <td class="label-cell">No. Induk</td>
                        <td class="colon-cell">:</td>
                        <td>{{ $student['student_id'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Tanggal Evaluasi</td>
                        <td class="colon-cell">:</td>
                        <td>{{ $student['date'] ?? '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="grades-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">Nama Surat</th>
                <th style="width: 8%;">Nilai</th>
                <th style="width: 22%;">Keterangan</th>
                <th style="width: 15%;">Nama Surat</th>
                <th style="width: 8%;">Nilai</th>
                <th style="width: 22%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < 20; $i++)
                <tr>
                    <td class="no-order">{{ $i + 1 }}.</td>
                    @php
                        $leftScore = $surahs[$i]['score'] ?? '';
                        $isLeftFilled = !empty($leftScore) || $leftScore === 0 || $leftScore === '0';
                    @endphp

                    <td class="surah-name {{ $isLeftFilled ? 'score-filled' : '' }}">{{ $surahs[$i]['name'] ?? '' }}
                    </td>

                    <td class="grade-val">
                        {{ $leftScore }}
                    </td>

                    <td class="keterangan-val">
                        {{ $surahs[$i]['keterangan'] ?? '' }}
                    </td>

                    @php
                        $rightIndex = $i + 20;
                        $rightScore = $surahs[$rightIndex]['score'] ?? '';
                        $isRightFilled = !empty($rightScore) || $rightScore === 0 || $rightScore === '0';
                    @endphp

                    <td class="surah-name {{ $isRightFilled ? 'score-filled' : '' }}">
                        {{ $surahs[$rightIndex]['name'] ?? '' }}</td>

                    <td class="grade-val">
                        {{ $rightScore }}
                    </td>

                    <td class="keterangan-val">
                        {{ $surahs[$rightIndex]['keterangan'] ?? '' }}
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>

    <table class="bottom-layout">
        <tr>
            <td style="width: 60%;">
                <div class="legend-title">Keterangan :</div>
                <table class="legend-table">
                    <thead>
                        <tr>
                            <th style="width: 60%;">Predikat</th>
                            <th>Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Mumtaz</td>
                            <td>90</td>
                        </tr>
                        <tr>
                            <td>Jayyid Jiddan</td>
                            <td>89 - 80</td>
                        </tr>
                        <tr>
                            <td>Jayyid</td>
                            <td>79 - 70</td>
                        </tr>
                        <tr>
                            <td>Rasib</td>
                            <td>69 - 60</td>
                        </tr>
                    </tbody>
                </table>

                <div class="catatan-title">Catatan :</div>
                <table class="catatan-table">
                    <tr>
                        <td style="width: 150px;">Telah Menghafal</td>
                        <td>: {{ $student['memorized'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Capaian Surat Terakhir</td>
                        <td>: {{ $student['last_surah'] ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table style="width: 100%;">
        <tr>
            <td style="width: 60%;">
            </td>
            <td style="width: 40%;">
                <div class="signature-name">
                    <div>Koordinator Tahfidz TK B</div>
                    <div style="margin-top: 80px;">({{ $teacherName }})</div>
                </div>
            </td>
        </tr>
    </table>
</body>

</html>
