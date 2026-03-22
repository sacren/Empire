<x-mail::message>
# Payment Receipt

Thank you, {{ $prospectName }}! We've received your payment.

**Amount:** {{ $amount }}
**Method:** {{ $method }}
**Date:** {{ $paidAt }}
**Remaining Balance:** {{ $remainingBalance }}

If you have any questions about your account, please don't hesitate to reach out.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
