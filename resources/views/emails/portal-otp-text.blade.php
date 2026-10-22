{{ __('orders.mail.otp_heading') }}

{{ __('orders.mail.otp_line1', ['purpose' => $purposeText, 'reference' => $order->reference]) }}

{{ $code }}

{{ __('orders.mail.otp_line2', ['minutes' => $minutes]) }}
