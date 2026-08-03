<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $note->title ?: 'Note' }} — {{ config('app.name') }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
    font-size: 14px; line-height: 1.65; color: #1a1a1a; background: #fff;
    padding: 32px 48px;
}
.print-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    border-bottom: 2px solid #128c7e; padding-bottom: 14px; margin-bottom: 24px;
}
.print-header-left h1 { font-size: 22px; font-weight: 700; color: #128c7e; margin-bottom: 4px; }
.print-meta { font-size: 12px; color: #666; }
.print-logo  { font-size: 11px; color: #999; text-align: right; }
.note-body { line-height: 1.7; }
.note-body h1 { font-size: 20px; margin: 16px 0 8px; }
.note-body h2 { font-size: 17px; margin: 14px 0 6px; }
.note-body h3 { font-size: 15px; margin: 12px 0 4px; }
.note-body p  { margin-bottom: 8px; }
.note-body ul, .note-body ol { padding-left: 24px; margin-bottom: 8px; }
.note-body li { margin-bottom: 3px; }
.note-body blockquote {
    border-left: 4px solid #128c7e; padding: 4px 12px;
    margin: 10px 0; background: #f0faf8; color: #333;
}
.note-body pre, .note-body code {
    font-family: 'Courier New', monospace; font-size: 12px;
    background: #f5f5f5; border-radius: 4px;
}
.note-body pre  { padding: 10px 14px; margin: 8px 0; overflow: auto; }
.note-body code { padding: 1px 4px; }
.note-body table {
    border-collapse: collapse; width: 100%; margin: 12px 0;
    font-size: 13px;
}
.note-body th, .note-body td {
    border: 1px solid #ccc; padding: 7px 10px; text-align: left;
}
.note-body th { background: #f0faf8; font-weight: 600; }
.note-body img { max-width: 100%; border-radius: 6px; margin: 8px 0; }
.note-body audio { display: block; margin: 8px 0; }
.print-footer {
    margin-top: 40px; padding-top: 12px; border-top: 1px solid #ddd;
    font-size: 11px; color: #999; display: flex; justify-content: space-between;
}
@media print {
    body { padding: 20mm 20mm; }
    .no-print { display: none !important; }
    .print-header { page-break-after: avoid; }
    table { page-break-inside: avoid; }
    img  { page-break-inside: avoid; }
}
</style>
</head>
<body>

<div class="print-header">
    <div class="print-header-left">
        <h1>{{ $note->title ?: __('Untitled Note') }}</h1>
        <div class="print-meta">
            <strong>{{ __('Contact') }}:</strong> {{ $conversation->contact_name ?? $conversation->contact_number }}
            &nbsp;·&nbsp;
            <strong>{{ __('Author') }}:</strong> {{ $note->author }}
            &nbsp;·&nbsp;
            <strong>{{ __('Date') }}:</strong> {{ $note->created_at->format('d M Y, H:i') }}
            @if($note->note_type && $note->note_type !== 'text')
                &nbsp;·&nbsp;
                <strong>{{ __('Type') }}:</strong> {{ ucfirst($note->note_type) }}
            @endif
        </div>
    </div>
    <div class="print-logo">
        {{ config('app.name') }}<br>
        <span style="font-size:10px">{{ now()->format('d M Y') }}</span>
    </div>
</div>

<div class="note-body">
    {!! $note->note !!}
</div>

<div class="print-footer">
    <span>{{ config('app.name') }} — {{ __('Conversation Note') }}</span>
    <span>{{ $conversation->contact_number }}</span>
</div>

<script>window.onload = () => window.print();</script>
</body>
</html>
