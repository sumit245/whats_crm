@props([
    'title' => '',
    'subtitle' => null,
    'breadcrumb' => [],
])

{{--
    Shared dashboard page header.
    Usage:
        <x-page-header title="Campaign" subtitle="Manage your blasts"
            :breadcrumb="['Reports', 'Campaign']">
            <a href="#" class="btn btn-primary btn-sm">Action</a>
        </x-page-header>
--}}
<div class="page-header">
    <div class="page-header__titles">
        <h1 class="page-header__title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="page-header__subtitle">{{ $subtitle }}</p>
        @endif
        @if (!empty($breadcrumb))
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('home') }}"><i class="bi bi-house-door"></i></a>
                    </li>
                    @foreach ($breadcrumb as $crumb)
                        <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}"
                            @if ($loop->last) aria-current="page" @endif>{{ $crumb }}</li>
                    @endforeach
                </ol>
            </nav>
        @endif
    </div>

    @if (isset($slot) && $slot->isNotEmpty())
        <div class="page-header__actions">
            {{ $slot }}
        </div>
    @endif
</div>
