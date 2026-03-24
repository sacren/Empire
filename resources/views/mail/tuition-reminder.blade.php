<x-mail::message>
# Tuition Reminder

Hello {{ $prospectName }}, this is a friendly reminder that you have an outstanding tuition balance.

**Cohort:** {{ $cohortName }}
**Total Tuition:** {{ $totalOwed }}
**Amount Paid:** {{ $totalPaid }}
**Remaining Balance:** {{ $remainingBalance }}

If you have any questions about your account or need to discuss payment options, please don't hesitate to reach out.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
