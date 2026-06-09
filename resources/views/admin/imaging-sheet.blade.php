<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Imaging-Blatt</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; margin: 0; }
        .sheet { padding: 28px 32px; page-break-after: always; }
        .sheet:last-child { page-break-after: auto; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .sub { font-size: 11px; color: #555; margin: 0 0 16px; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        table.meta td { padding: 4px 6px; vertical-align: top; border: 1px solid #ccc; }
        table.meta td.label { width: 130px; background: #f3f4f6; font-weight: bold; }
        .write-in { display: inline-block; min-width: 180px; border-bottom: 1px solid #111; height: 14px; }
        h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 2px solid #111; padding-bottom: 3px; }
        ul.checklist { list-style: none; margin: 0; padding: 0; }
        ul.checklist li { padding: 4px 0; }
        .box { display: inline-block; width: 12px; height: 12px; border: 1px solid #111; margin-right: 8px; vertical-align: middle; }
        .special { color: #b45309; font-weight: bold; }
        .empty { color: #777; font-style: italic; }
    </style>
</head>
<body>
@foreach ($sheets as $sheet)
    <div class="sheet">
        <h1>Imaging-Blatt</h1>
        <p class="sub">Laptop-Austausch · Kreis Groß-Gerau</p>

        <table class="meta">
            <tr>
                <td class="label">Mitarbeiter:in</td>
                <td>{{ $sheet['name'] }}</td>
                <td class="label">KVGG-Nr.</td>
                <td>{{ $sheet['kvgg'] }}</td>
            </tr>
            <tr>
                <td class="label">Abteilung</td>
                <td>{{ $sheet['abteilung'] }}</td>
                <td class="label">Status</td>
                <td>{{ $sheet['status'] }}</td>
            </tr>
            <tr>
                <td class="label">Termin</td>
                <td>{{ $sheet['date'] }}, {{ $sheet['time'] }}</td>
                <td class="label">Kalenderwoche</td>
                <td>{{ $sheet['kw'] ? 'KW '.$sheet['kw'] : '—' }}</td>
            </tr>
            <tr>
                <td class="label">Alt-PC-Nummer</td>
                <td>{{ $sheet['old_pc'] }}</td>
                <td class="label">Neue PC-Nummer</td>
                <td><span class="write-in"></span></td>
            </tr>
            <tr>
                <td class="label">Neue Inventarnr.</td>
                <td><span class="write-in"></span></td>
                <td class="label">Bearbeitet von</td>
                <td><span class="write-in"></span></td>
            </tr>
        </table>

        <h2>Standardsoftware (auf jedem Laptop)</h2>
        <ul class="checklist">
            @forelse ($sheet['standard'] as $name)
                <li><span class="box"></span>{{ $name }}</li>
            @empty
                <li class="empty">Keine Standardsoftware im Katalog hinterlegt.</li>
            @endforelse
        </ul>

        <h2>Zusätzlich angefordert</h2>
        <ul class="checklist">
            @forelse ($sheet['requested'] as $name)
                <li>
                    <span class="box"></span>
                    @if (str_ends_with($name, '(Spezial)'))
                        <span class="special">{{ $name }}</span>
                    @else
                        {{ $name }}
                    @endif
                </li>
            @empty
                <li class="empty">Keine zusätzliche Software angefordert.</li>
            @endforelse
        </ul>
    </div>
@endforeach
</body>
</html>
