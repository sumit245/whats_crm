<x-layout-dashboard title="{{ __('Ad Creatives') }}">
@if (session()->has('alert'))
<x-alert>
    @slot('type', session('alert')['type'])
    @slot('msg', session('alert')['msg'])
</x-alert>
@endif

<x-page-header title="{{ __('Creative Library') }}"
    subtitle="{{ __('Reusable content pieces — images, videos, carousels, stories, reels') }}"
    :breadcrumb="[__('Ads Manager'), __('Creatives')]" />

<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCreativeModal">
        <i class="bi bi-plus-lg me-1"></i>{{ __('New Creative') }}
    </button>
</div>

<div class="row g-3">
@if($creatives->isEmpty())
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <x-no-data colspan="1" text="{{ __('No creatives yet') }}" />
        </div>
    </div>
@else
    @foreach($creatives as $cr)
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            @if($cr->firstMediaUrl())
            <img src="{{ asset($cr->media_paths[0] ?? '') }}" class="card-img-top" style="height:160px;object-fit:cover" alt="">
            @else
            <div class="bg-light d-flex align-items-center justify-content-center" style="height:120px">
                <i class="bi {{ $cr->formatIcon() }} fs-1 text-muted"></i>
            </div>
            @endif
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                    <div class="fw-semibold small text-truncate">{{ $cr->name }}</div>
                    <span class="badge bg-{{ $cr->status === 'ready' ? 'success' : 'secondary' }} flex-shrink-0">{{ ucfirst($cr->format) }}</span>
                </div>
                @if($cr->headline)
                <div class="small fw-semibold text-truncate">{{ $cr->headline }}</div>
                @endif
                @if($cr->body)
                <div class="small text-muted mt-1" style="overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">{{ $cr->body }}</div>
                @endif
                @if($cr->cta_text)
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary mt-2">{{ $cr->cta_text }}</span>
                @endif
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                <div class="text-muted" style="font-size:11px">{{ $cr->created_at->format('d M Y') }}</div>
                <div class="d-flex gap-1">
                    <a href="{{ route('ads.creatives.show', $cr) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Preview & Edit') }}">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('ads.campaigns.create', ['creative_id' => $cr->id]) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Use in campaign') }}">
                        <i class="bi bi-megaphone"></i>
                    </a>
                    <form method="POST" action="{{ route('ads.creatives.destroy', $cr) }}" class="d-inline"
                          onsubmit="return confirm('{{ __('Delete creative?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach
@endif
</div>

@if($creatives->hasPages())
<div class="mt-3">{{ $creatives->links() }}</div>
@endif

{{-- Add Creative Modal --}}
<div class="modal fade" id="addCreativeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('ads.creatives.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('New Creative') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Format') }}</label>
                            <select name="format" class="form-select" id="modalFormat">
                                <option value="text">Text</option>
                                <option value="image">Image</option>
                                <option value="video">Video</option>
                                <option value="carousel">Carousel</option>
                                <option value="story">Story</option>
                                <option value="reel">Reel</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">{{ __('Headline') }}</label>
                            <input type="text" name="headline" class="form-control" maxlength="255">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">{{ __('Body') }}</label>
                            <textarea name="body" class="form-control" rows="3" maxlength="2000"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('CTA Text') }}</label>
                            <input type="text" name="cta_text" class="form-control" maxlength="50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('CTA URL') }}</label>
                            <input type="url" name="cta_url" class="form-control">
                        </div>
                        <div class="col-12" id="modalMediaUpload">
                            <label class="form-label fw-semibold">{{ __('Media') }}</label>
                            <input type="file" name="media[]" class="form-control" multiple accept="image/*,video/*">
                        </div>
                        <div class="col-12 d-none" id="modalCarouselSection">
                            <label class="form-label fw-semibold">{{ __('Carousel Cards JSON') }}</label>
                            <textarea name="carousel_cards" class="form-control font-monospace" rows="4"
                                      placeholder='[{"image":"url","headline":"H","description":"D","cta_url":"https://..."}]'></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Creative') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

</x-layout-dashboard>

<script>
document.getElementById('modalFormat')?.addEventListener('change', function() {
    const isCarousel = this.value === 'carousel';
    document.getElementById('modalCarouselSection').classList.toggle('d-none', !isCarousel);
    document.getElementById('modalMediaUpload').classList.toggle('d-none', isCarousel);
});
</script>
