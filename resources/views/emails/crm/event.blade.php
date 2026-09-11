@component('mail::message')

# {{ $title }}

{{ $subtitle }}

@component('mail::button', ['url' => $url, 'color' => 'primary'])
Lihat Detail
@endcomponent

Salam,
{{ config('company.name') }}

@endcomponent
