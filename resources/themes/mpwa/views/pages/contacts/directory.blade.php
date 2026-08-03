<x-layout-dashboard title="{{ __('Contacts') }}">

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold"><i class="bi bi-people-fill me-1"></i>{{ __('Contacts') }}</span>
        <div class="d-flex gap-2">
            <a href="{{ route('phonebook') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-telephone-fill me-1"></i>{{ __('Phone Book') }}
            </a>
            <a href="{{ route('contacts.import') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-upload me-1"></i>{{ __('Import') }}
            </a>
        </div>
    </div>

    <div class="card-body pb-2">
        <form method="GET" class="d-flex gap-2 mb-3">
            <div class="input-group input-group-sm" style="max-width:340px">
                <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control" placeholder="{{ __('Search name or number…') }}" value="{{ $q }}">
            </div>
            @if($q)
                <a href="{{ route('contacts.directory') }}" class="btn btn-sm btn-outline-secondary">{{ __('Clear') }}</a>
            @endif
        </form>

        @if($contacts->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-people d-block mb-2" style="font-size:2.5rem"></i>
                @if($q)
                    {{ __('No contacts match ":q".', ['q' => $q]) }}
                @else
                    {{ __('No contacts yet. Import a CSV or add contacts via Phone Book.') }}
                @endif
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-2">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Number') }}</th>
                        <th>{{ __('Phonebook') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contacts as $contact)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                                     style="width:30px;height:30px;font-size:0.8rem;flex-shrink:0">
                                    {{ strtoupper(mb_substr($contact->name ?: $contact->number, 0, 1)) }}
                                </div>
                                <span class="fw-semibold">{{ $contact->name ?: '—' }}</span>
                            </div>
                        </td>
                        <td class="font-monospace small">{{ $contact->number }}</td>
                        <td>
                            @if($contact->tag)
                                <span class="badge bg-secondary-subtle text-secondary">{{ $contact->tag->name }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('contact.timeline', $contact->number) }}"
                               class="btn btn-sm btn-outline-primary"
                               title="{{ __('360° Profile') }}">
                                <i class="bi bi-person-lines-fill me-1"></i>360°
                            </a>
                            @php $conv = $convMap->get($contact->number); @endphp
                            @if($conv)
                                <a href="{{ route('chat.show', $conv->id) }}?conv_status={{ $conv->conversation_status }}"
                                   class="btn btn-sm btn-outline-secondary ms-1"
                                   title="{{ __('Open Chat') }}">
                                    <i class="bi bi-chat-dots"></i>
                                </a>
                            @else
                                <span class="btn btn-sm btn-outline-secondary ms-1 disabled"
                                      title="{{ __('No conversation yet') }}">
                                    <i class="bi bi-chat-dots"></i>
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex align-items-center justify-content-between">
            <small class="text-muted">
                {{ __('Showing :from–:to of :total', ['from' => $contacts->firstItem(), 'to' => $contacts->lastItem(), 'total' => $contacts->total()]) }}
            </small>
            {{ $contacts->links() }}
        </div>
        @endif
    </div>
</div>

</x-layout-dashboard>
