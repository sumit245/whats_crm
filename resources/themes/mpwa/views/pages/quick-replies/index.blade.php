<x-layout-dashboard title="{{ __('Quick Replies') }}">

<x-page-header
    title="{{ __('Quick Replies') }}"
    subtitle="{{ __('Canned responses you can insert in chat by typing / followed by the shortcut.') }}"
/>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ __('Add Quick Reply') }}</h6></div>
            <div class="card-body">
                <form action="{{ route('quick-replies.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">
                            {{ __('Shortcut') }}
                            <span class="text-muted fw-normal">(no spaces, e.g. <code>greet</code>)</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">/</span>
                            <input type="text" name="shortcut" class="form-control @error('shortcut') is-invalid @enderror"
                                value="{{ old('shortcut') }}" placeholder="greet" required pattern="\S+">
                            @error('shortcut')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Title') }}</label>
                        <input type="text" name="title" class="form-control form-control-sm @error('title') is-invalid @enderror"
                            value="{{ old('title') }}" placeholder="{{ __('Greeting message') }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Message Body') }}</label>
                        <textarea name="body" rows="4"
                            class="form-control form-control-sm @error('body') is-invalid @enderror"
                            placeholder="{{ __('Hello! Thanks for reaching out. How can we help you today?') }}"
                            required>{{ old('body') }}</textarea>
                        @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i>{{ __('Save Quick Reply') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0">{{ __('Saved Replies') }}</h6>
                <span class="badge bg-secondary">{{ $replies->count() }}</span>
            </div>
            <div class="card-body p-0">
                @if($replies->isEmpty())
                    <div class="text-center text-muted p-4 small">
                        <i class="bi bi-lightning fs-3 d-block mb-2 opacity-25"></i>
                        {{ __('No quick replies yet. Add one on the left.') }}
                    </div>
                @else
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:120px">{{ __('Shortcut') }}</th>
                                <th>{{ __('Title') }}</th>
                                <th>{{ __('Body') }}</th>
                                <th style="width:60px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($replies as $r)
                            <tr>
                                <td><code class="small">/{{ $r->shortcut }}</code></td>
                                <td class="small fw-semibold">{{ $r->title }}</td>
                                <td class="small text-muted" style="max-width:260px">
                                    <span class="text-truncate d-block" style="overflow:hidden;white-space:nowrap">{{ $r->body }}</span>
                                </td>
                                <td>
                                    <form action="{{ route('quick-replies.destroy', $r) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0"
                                            onclick="return confirm('{{ __('Delete this quick reply?') }}')"
                                            title="{{ __('Delete') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

</x-layout-dashboard>
