<script>
@php $toastrType = $type === 'danger' ? 'error' : $type; @endphp
toastr['{{ $toastrType }}']('{{ addslashes($msg) }}');
</script>
