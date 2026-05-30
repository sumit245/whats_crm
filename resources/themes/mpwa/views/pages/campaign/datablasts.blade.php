<x-layout-dashboard title="{{__('Data Campaign')}}">
    @if (session()->has('alert'))
        <x-alert>
            @slot('type', session('alert')['type'])
            @slot('msg', session('alert')['msg'])
        </x-alert>
    @endif
    <x-page-header title="{{ __('Data Campaign') }}"
        subtitle="{{ $campaign_name }}"
        :breadcrumb="[__('Campaign'), $campaign_name]">
        <a href="{{ route('campaigns') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> {{ __('Back to Campaigns') }}
        </a>
    </x-page-header>

    {{-- table --}}
    <div class="row">
        <div class="col-12 col-lg-12 d-flex">
            <div class="card w-100">
                <div class="card-header py-3">
                    <div class="row g-3">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>{{__('Receiver')}}</th>
                                    <th>{{__('Status')}}</th>
                                    <th>{{__('Last Updated')}}</th>
                                  
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($blasts as $blast)
                                    <tr>
                                        <td>{{ $blast->receiver }}</td>
                                      
                                       
                                      
                                       
                                        <td>
                                            @if ($blast->status == 'success')
                                                <span class="badge rounded-pill bg-success">{{__('Sent')}}</span>
                                            @elseif ($blast->status == 'pending')
                                                <span class="badge rounded-pill bg-warning">{{__('Pending')}}</span>
                                            @else
                                                <span
                                                    class="badge rounded-pill bg-danger">{{ $blast->status }}</span>
                                            @endif
                                        </td>
                                         <td>{{ $blast->updated_at }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <nav aria-label="Page navigation example">
                        <ul class="pagination">
                            <li class="page-item {{ $blasts->currentPage() == 1 ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $blasts->previousPageUrl() }}">{{__('Previous')}}</a>
                            </li>

                            @for ($i = 1; $i <= $blasts->lastPage(); $i++)
                                <li class="page-item {{ $blasts->currentPage() == $i ? 'active' : '' }}">
                                    <a class="page-link" href="{{ $blasts->url($i) }}">{{ $i }}</a>
                                </li>
                            @endfor

                            <li
                                class="page-item {{ $blasts->currentPage() == $blasts->lastPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $blasts->nextPageUrl() }}">{{__('Next')}}</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
     
    </div>
    {{-- end table --}}

</x-layout-dashboard>

