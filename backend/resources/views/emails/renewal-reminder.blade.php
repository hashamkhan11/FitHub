<x-mail::message>
# Your membership is expiring soon

Hi {{ $membership->member->name }},

Your **{{ $membership->plan->name }}** plan expires on **{{ $membership->end_date->format('M j, Y') }}**.

Renew before then to keep your gym access without interruption.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
