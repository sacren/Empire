<x-mail::message>
# Welcome, {{ $prospectName }}!

Your enrollment has been confirmed. Here are your details:

**Cohort:** {{ $cohortName }}
**Enrolled On:** {{ $enrolledAt }}
**Tuition:** {{ $tuitionAmount }}

We're excited to have you join us. If you have any questions, please don't hesitate to reach out.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
