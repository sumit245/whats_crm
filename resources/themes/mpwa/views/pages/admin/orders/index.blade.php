<x-layout-dashboard title="{{ __('Orders') }}">
	<x-page-header title="{{ __('Orders') }}"
		subtitle="{{ __('Subscription and plan purchase orders') }}"
		:breadcrumb="[__('Admin'), __('Orders')]" />
	@if (session()->has('alert'))
	<x-alert>
		@slot('type', session('alert')['type'])
		@slot('msg', session('alert')['msg'])
	</x-alert>
	@endif
	@if ($errors->any())
	<div class="alert alert-danger">
		<ul>
			@foreach ($errors->all() as $error)
			<li>{{ $error }}</li>
			@endforeach
		</ul>
	</div>
	@endif
<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<h5>{{ __('Orders') }}</h5>
			</div>
			<div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{__('User')}}</th>
                            <th>{{__('Plan')}}</th>
                            <th>{{__('Order ID')}}</th>
                            <th>{{__('Amount')}}</th>
							<th>{{ __('Payment Gateway') }}</th>
                            <th>{{__('Status')}}</th>
                            <th>{{__('Created At')}}</th>
                        </tr>
                    </thead>
                    <tbody>
					@if (count($orders) != 0)
                        @foreach($orders as $order)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $order->user->username }}</td>
                            <td>{{ $order->plan->title }}</td>
                            <td>{{ $order->order_id }}</td>
                            <td>{{ number_format($order->amount) }}</td>
							<td>{{ ucfirst($order->payment_gateway ?? __('Unknown')) }}</td>
                            <td>
                                <span class="badge bg-{{ $order->status === 'completed' ? 'success' : 'danger' }}">
                                    {{__(ucfirst($order->status)) }}
                                </span>
                            </td>
                            <td dir="ltr" class="text-start">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        @endforeach
					@else
						<tr>
							<td colspan="7" class="text-center">{{__('No orders')}}</td>
						</tr>
					@endif
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $orders->links() }}
            </div>
    </div>
			</div>
	</div>
</div>
</x-layout-dashboard>