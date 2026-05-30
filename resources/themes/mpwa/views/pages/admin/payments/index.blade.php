<x-layout-dashboard title="{{ __('Payment Gateways') }}">

    <x-page-header title="{{ __('Payment Gateways') }}"
        subtitle="{{ __('Enable and configure your payment providers') }}"
        :breadcrumb="[__('Admin'), __('Payment Gateways')]" />
        <div class="card-body">
            <form action="{{ route('admin.payments.update') }}" method="POST">
    @csrf
	@foreach ($gateways as $gateway)
    <div class="card">
        <div class="card-header">
            <h6>{{ ucfirst($gateway['name']) }}</h6>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach ($gateway['config'] as $key => $option)
                    <div class="col-md-6">
                        <label for="{{$key}}" class="form-label mt-2" id="{{$key}}">{{str_replace('_', ' ', ucfirst($key))}}</label>
                        @if($key == 'status')
                            <select name="gateway[{{$gateway['name']}}][{{$key}}]" class="form-control">
                                <option value="disable">Disable</option>
                                <option value="enable" @if($option == 'enable') selected @endif>Enable</option>
                            </select>
                        @else
                            <input name="gateway[{{$gateway['name']}}][{{$key}}]" id="{{$key}}" class="form-control" value="{{$option}}" />
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
	@endforeach

    <button type="submit" class="btn btn-primary mt-3">{{ __('Save Changes') }}</button>
</form>
        </div>
    
</x-layout-dashboard>
