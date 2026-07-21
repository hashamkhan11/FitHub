<x-mail::message>
# Password reset code

Use this code to reset your FitHub password:

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

This code expires in 10 minutes. If you didn't request this, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
