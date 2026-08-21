@component('mail::message')
{{ __('tracking.shared.mail_intro', ['brand' => $car->brand, 'model' => $car->model, 'year' => $car->year]) }}

{{ __('tracking.shared.mail_body') }}

@component('mail::button', ['url' => $trackingUrl])
{{ __('tracking.shared.mail_cta') }}
@endcomponent

{{ __('tracking.shared.mail_footer') }}

{{ config('app.name') }}
@endcomponent
