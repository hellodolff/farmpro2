<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 9px; }
        h1 { margin-bottom: 4px; font-size: 18px; }
        .meta { margin-bottom: 14px; color: #64748b; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th { background: #ecf8f1; color: #164c33; text-align: left; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 4px; word-wrap: break-word; }
        .empty { text-align: center; color: #64748b; padding: 20px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        <strong>{{ config('farmpro.name') }}</strong><br>
        Periode: {{ $start->format('d/m/Y') }} - {{ $end->format('d/m/Y') }}<br>
        Dicetak: {{ $generatedAt->format('d/m/Y H:i') }} WIB
    </div>

    <table>
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $column)
                        <td>{{ $row[$column] ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) }}" class="empty">Tidak ada data pada filter ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
