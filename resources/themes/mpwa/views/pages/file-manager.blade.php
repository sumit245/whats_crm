<x-layout-dashboard title="{{__('File manager')}}">
    <div class="app-content">
        <div class="content-wrapper">
            
            <div class="container-fluid">
                <div class="row">
                    @if (session()->has('alert'))
                    <x-alert>
                        @slot('type',session('alert')['type'])
                        @slot('msg',session('alert')['msg'])
                    </x-alert>
                 @endif
                 <iframe src="{{url('/laravel-filemanager')}}" style="width: 100%; height: 500px; overflow: hidden; border: none;"></iframe>
                </div>
               
            </div>
        </div>
    </div>

    <script>
        $('#server').on('change',function(){
           let type = $('#server :selected').val();
            console.log(type);
            if(type === 'other'){
                    $('.formUrlNode').removeClass('d-none')
                } else {
                $('.formUrlNode').addClass('d-none')

            }
        })
    </script>
</x-layout-dashboard>