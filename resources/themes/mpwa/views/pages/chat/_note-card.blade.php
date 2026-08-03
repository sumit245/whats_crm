@php
    $typeIcon = match($note->note_type ?? 'text') {
        'voice'   => 'bi-mic-fill',
        'drawing' => 'bi-brush-fill',
        default   => 'bi-journal-text',
    };
    $excerpt = $note->title ?: \Illuminate\Support\Str::limit(strip_tags($note->note), 45);
@endphp
<div class="note-card {{ $note->is_internal ? '' : 'note-card-visible' }}" data-id="{{ $note->id }}"
     data-title="{{ e($note->title ?? '') }}"
     data-type="{{ $note->note_type ?? 'text' }}"
     data-internal="{{ $note->is_internal ? '1' : '0' }}">
    <div class="note-card-header">
        <i class="bi {{ $typeIcon }} note-type-icon"></i>
        <span class="note-card-title">{{ $excerpt ?: __('(empty)') }}</span>
        <div class="note-card-actions">
            <button class="btn btn-xs" onclick="openNoteEditor({{ $note->id }})" title="{{ __('Edit') }}">
                <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-xs text-danger" onclick="deleteNote({{ $note->id }})" title="{{ __('Delete') }}">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
    <div class="note-card-meta">
        {{ $note->author }} · {{ $note->created_at->format('d M H:i') }}
        @if(!$note->is_internal)
            <span class="badge bg-info-subtle text-info ms-1">{{ __('Visible') }}</span>
        @endif
    </div>
</div>
