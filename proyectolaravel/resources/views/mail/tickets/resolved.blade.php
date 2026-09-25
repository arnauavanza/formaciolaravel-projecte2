<x-mail::message>
# Ticket resolved

Your ticket has been resolved.

**Title:** {{ $ticket->title }}

**Description:** {{ $ticket->description }}

**Status:** {{ $ticket->status->value }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>