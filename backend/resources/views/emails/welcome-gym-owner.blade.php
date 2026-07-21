<x-mail::message>
# Welcome to FitHub, {{ $gym->name }}!

RankSol has set up your gym's account. Here's how to log in:

- **URL:** {{ url('/login') }}
- **Email:** {{ $ownerEmail }}
- **Temporary password:** {{ $temporaryPassword }}

<x-mail::button :url="url('/login')">
Log in to your dashboard
</x-mail::button>

For security, please change your password after your first login (Account → Change Password).

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
