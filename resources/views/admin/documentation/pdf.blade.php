<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #32475c; }
        .cover { text-align: center; padding-top: 220px; page-break-after: always; }
        .cover .brand { color: #3833C8; font-size: 16px; font-weight: bold; margin-bottom: 40px; }
        .cover h1 { font-size: 28px; margin-bottom: 10px; }
        .cover .generated { color: #8897ab; font-size: 11px; margin-top: 40px; }
        .toc { page-break-after: always; }
        .toc h2 { font-size: 18px; border-bottom: 2px solid #3833C8; padding-bottom: 8px; }
        .toc ol { font-size: 13px; line-height: 2; }
        .section { page-break-before: always; }
        .section:first-of-type { page-break-before: auto; }
        .section h1 { font-size: 18px; color: #3833C8; border-bottom: 1px solid #eceef1; padding-bottom: 6px; }
        .section h2 { font-size: 15px; margin-top: 20px; }
        .section h3 { font-size: 13px; margin-top: 14px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table th, table td { border: 1px solid #dfe3ea; padding: 5px 8px; font-size: 10.5px; text-align: left; }
        table th { background: #f4f5fa; }
        code { background: #f4f5fa; padding: 1px 4px; border-radius: 3px; font-size: 10.5px; }
        pre { background: #f4f5fa; padding: 8px 10px; border-radius: 4px; font-size: 10px; }
        ul, ol { padding-left: 20px; }
    </style>
</head>
<body>
    <div class="cover">
        <div class="brand">@include('partials.brand-wordmark')</div>
        <h1>{{ $title }}</h1>
        <div class="generated">Generated on {{ now()->format('d M Y') }}</div>
    </div>

    <div class="toc">
        <h2>Contents</h2>
        <ol>
            @foreach ($sections as $section)
                <li>{{ $section['title'] }}</li>
            @endforeach
        </ol>
    </div>

    @foreach ($sections as $section)
        <div class="section">
            <h1>{{ $section['title'] }}</h1>
            {!! $section['html'] !!}
        </div>
    @endforeach
</body>
</html>
