{{ __('tracking.shared.mail_intro', ['brand' => $car->brand, 'model' => $car->model, 'year' => $car->year]) }}

{{ __('tracking.shared.mail_body') }}

{{ $trackingUrl }}

{{ __('tracking.shared.mail_footer') }}

{{ config('app.name') }}
