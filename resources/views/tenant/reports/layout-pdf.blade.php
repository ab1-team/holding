<!DOCTYPE html>
<html lang="id">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $reportLabel ?? 'Laporan' }} — {{ $period ?? '' }}</title>
    <style>
        * { font-family: Arial, Helvetica, sans-serif; }

        @page { margin: 14mm 12mm; }

        body { font-size: 10px; color: #000; margin: 0; }

        .pdf-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }

        .pdf-subtitle {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 2px 0 12px 0;
        }

        .pdf-meta {
            font-size: 9px;
            color: #444;
            margin-bottom: 4px;
            text-align: center;
        }

        .pdf-entity {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }

        table.pdf {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        table.pdf th,
        table.pdf td {
            padding: 3px 5px;
            vertical-align: top;
        }

        table.pdf thead th {
            background: #000;
            color: #fff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            text-align: left;
            border: 1px solid #000;
        }

        table.pdf thead th.num { text-align: right; }
        table.pdf thead th.center { text-align: center; }

        table.pdf tbody td {
            border-bottom: 1px solid #999;
        }

        table.pdf tbody tr.lev1 td {
            background: #4a4a4a;
            color: #fff;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }

        table.pdf tbody tr.lev2 td {
            background: #a7a7a7;
            font-weight: bold;
        }

        table.pdf tbody tr.lev3 td {
            background: #e6e6e6;
        }

        table.pdf tbody tr.lev3.alt td {
            background: #ffffff;
        }

        table.pdf tbody tr.subtotal td {
            background: #a7a7a7;
            font-weight: bold;
            border-top: 1px solid #000;
        }

        table.pdf tbody tr.section-header td {
            background: #c8c8c8;
            font-weight: bold;
            text-transform: uppercase;
        }

        table.pdf tfoot td {
            background: #c8c8c8;
            font-weight: bold;
            border-top: 2px solid #000;
        }

        .num { text-align: right; font-family: 'Courier New', Courier, monospace; white-space: nowrap; }
        .center { text-align: center; }
        .indent-1 { padding-left: 14px !important; }
        .indent-2 { padding-left: 28px !important; }
        .indent-3 { padding-left: 42px !important; }

        .signature {
            margin-top: 28px;
            width: 100%;
        }

        .signature td {
            text-align: center;
            font-size: 10px;
            padding: 2px 6px;
        }

        .signature .role {
            text-transform: uppercase;
            font-weight: bold;
            font-size: 9px;
        }

        .signature .name {
            margin-top: 56px;
            font-weight: bold;
            text-decoration: underline;
        }

        .narasi {
            text-align: justify;
            font-size: 10px;
            line-height: 1.45;
            margin: 4px 0 10px 0;
        }

        .narasi-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
            margin-top: 8px;
        }

        .page-break { page-break-after: always; }
    </style>
</head>

<body>
    @yield('content')
</body>

</html>
