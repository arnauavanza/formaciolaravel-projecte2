<x-mail::message>
# Ticket assigned

A ticket has been assigned to you.

**Title:** {{ $ticket->title }}

**Description:** {{ $ticket->description }}

**Status:** {{ $ticket->status->value }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>