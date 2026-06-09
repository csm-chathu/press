<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        .header { border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 12px; }
        .title { font-size: 16px; font-weight: bold; margin: 0 0 4px; }
        .muted { color: #666; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .right { text-align: right; }
        .logo { width: 48px; height: 48px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <table style="border: none;">
            <tr>
                <td style="border: none; width: 60px;">
                    @if(!empty($restaurant['logo_url']))
                        <img src="{{ $restaurant['logo_url'] }}" class="logo" alt="logo">
                    @endif
                </td>
                <td style="border: none;">
                    <p class="title">{{ $restaurant['name'] ?? 'Restaurant' }}</p>
                    <div class="muted">{{ $restaurant['address'] ?? '' }}</div>
                    <div class="muted">{{ trim(($restaurant['city'] ?? '') . ', ' . ($restaurant['country'] ?? ''), ', ') }}</div>
                </td>
                <td style="border: none; text-align: right;">
                    <p class="title" style="font-size: 14px;">{{ $title }}</p>
                    @if(!empty($dateRange))<div class="muted">{{ $dateRange }}</div>@endif
                    <div class="muted">Generated: {{ $generatedAt }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ is_scalar($cell) || $cell === null ? (string) $cell : json_encode($cell) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}">No data</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
