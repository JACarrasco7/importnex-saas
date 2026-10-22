{{ $heading }}

@if ($greeting)
{{ $greeting }}

@endif
@foreach ($lines as $line)
{{ $line }}

@endforeach
@if ($actionUrl && $actionLabel)
{{ $actionLabel }}: {{ $actionUrl }}

@endif
@if ($forClient)
{{ __('orders.mail.signature') }}

{{ __('orders.mail.footer') }}
@endif
