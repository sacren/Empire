<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-900 antialiased">

        {{-- Header --}}
        <header class="w-full border-b border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
            <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
                <span class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ config('app.name') }}
                </span>
                <a href="{{ route('login') }}" class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">
                    {{ __('Sign In') }}
                </a>
            </div>
        </header>

        {{-- Hero --}}
        <main>
            <section class="max-w-6xl mx-auto px-6 py-24 text-center">
                <h1 class="text-5xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 sm:text-6xl">
                    {{ __('Train for') }}<br>
                    <span class="text-accent">{{ __("tomorrow's trades") }}</span>
                </h1>
                <p class="mt-6 text-lg text-zinc-500 dark:text-zinc-400 max-w-2xl mx-auto">
                    {{ __('Empire Trade School prepares students for high-demand careers through hands-on, instructor-led training. Our SkillPath program is built for those ready to work.') }}
                </p>
                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('inquiry.form') }}" class="inline-flex items-center justify-center rounded-lg bg-accent px-8 py-3 text-sm font-semibold text-white shadow-sm hover:bg-accent/90 transition-colors">
                        {{ __('Apply Now') }}
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-8 py-3 text-sm font-semibold text-zinc-700 dark:text-zinc-300 shadow-sm hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        {{ __('Sign In') }}
                    </a>
                </div>
            </section>

            {{-- Program Highlights --}}
            <section class="border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/50">
                <div class="max-w-6xl mx-auto px-6 py-20">
                    <h2 class="text-center text-2xl font-bold text-zinc-900 dark:text-zinc-100 mb-12">
                        {{ __('Why SkillPath?') }}
                    </h2>
                    <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="text-center">
                            <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-xl bg-accent/10">
                                <svg class="size-6 text-accent" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.653-4.654m5.424-5.424 4.224-4.224a.75.75 0 0 1 1.06 1.06l-4.223 4.223m-2.06-2.06L12 9.75" /></svg>
                            </div>
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Hands-On Training') }}</h3>
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Learn by doing with real tools, real environments, and real instructors.') }}</p>
                        </div>
                        <div class="text-center">
                            <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-xl bg-accent/10">
                                <svg class="size-6 text-accent" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                            </div>
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Small Cohorts') }}</h3>
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Focused groups mean more attention, better outcomes, and a stronger cohort bond.') }}</p>
                        </div>
                        <div class="text-center">
                            <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-xl bg-accent/10">
                                <svg class="size-6 text-accent" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" /></svg>
                            </div>
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Certified Credentials') }}</h3>
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Graduate with industry-recognized certifications employers trust.') }}</p>
                        </div>
                        <div class="text-center">
                            <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-xl bg-accent/10">
                                <svg class="size-6 text-accent" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" /></svg>
                            </div>
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Job Placement Support') }}</h3>
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Our team works with local employers to connect graduates with real opportunities.') }}</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- CTA Banner --}}
            <section class="max-w-6xl mx-auto px-6 py-20 text-center">
                <h2 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">
                    {{ __('Ready to get started?') }}
                </h2>
                <p class="mt-4 text-zinc-500 dark:text-zinc-400">
                    {{ __('Submit an inquiry and our admissions team will be in touch within one business day.') }}
                </p>
                <a href="{{ route('inquiry.form') }}" class="mt-8 inline-flex items-center justify-center rounded-lg bg-accent px-8 py-3 text-sm font-semibold text-white shadow-sm hover:bg-accent/90 transition-colors">
                    {{ __('Start Your Application') }}
                </a>
            </section>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-zinc-200 dark:border-zinc-800">
            <div class="max-w-6xl mx-auto px-6 py-6 text-center">
                <p class="text-sm text-zinc-400">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
                </p>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
