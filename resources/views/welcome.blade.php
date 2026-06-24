<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'STAR School Management System') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-950 text-white antialiased">
        <div class="relative isolate overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(245,158,11,0.28),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(14,165,233,0.18),_transparent_28%)]"></div>

            <main class="relative mx-auto flex min-h-screen max-w-6xl flex-col justify-center px-6 py-16 lg:px-10">
                <div class="grid items-center gap-12 lg:grid-cols-[1.1fr_0.9fr]">
                    <div>
                        <div class="inline-flex items-center rounded-full border border-amber-300/30 bg-amber-300/10 px-4 py-1 text-sm font-semibold tracking-wide text-amber-200">
                            STAR Private High School
                        </div>
                        <h1 class="mt-6 max-w-3xl text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl">
                            School operations in one clear management system.
                        </h1>
                        <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
                            Manage students, enrollments, attendance, finance, ID cards, reports, wallets, and POS workflows from one internal platform built for STAR School.
                        </p>

                        <div class="mt-10 flex flex-wrap gap-4">
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-xl bg-amber-400 px-6 py-3 text-base font-bold text-slate-950 shadow-lg shadow-amber-400/20 transition hover:bg-amber-300">
                                    Open Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex items-center rounded-xl bg-amber-400 px-6 py-3 text-base font-bold text-slate-950 shadow-lg shadow-amber-400/20 transition hover:bg-amber-300">
                                    Sign In
                                </a>
                            @endauth
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-white/10 bg-white/5 p-8 shadow-2xl shadow-slate-950/40 backdrop-blur">
                        <div class="flex items-center gap-4">
                            <x-application-logo class="h-16 w-16 rounded-2xl bg-white p-2" />
                            <div>
                                <div class="text-2xl font-black tracking-wide text-white">STAR</div>
                                <div class="text-sm font-semibold uppercase tracking-[0.28em] text-slate-300">School Management System</div>
                            </div>
                        </div>

                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-5">
                                <div class="text-sm font-semibold text-amber-200">Academic</div>
                                <div class="mt-2 text-sm leading-6 text-slate-300">Attendance, marks, report cards, rooms, sections, and student daily reports.</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-5">
                                <div class="text-sm font-semibold text-amber-200">Finance</div>
                                <div class="mt-2 text-sm leading-6 text-slate-300">Invoices, payments, discounts, receivables, fee plans, and audit-ready records.</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-5">
                                <div class="text-sm font-semibold text-amber-200">Identity</div>
                                <div class="mt-2 text-sm leading-6 text-slate-300">Student and staff ID cards, barcode support, and visible field settings.</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-5">
                                <div class="text-sm font-semibold text-amber-200">Wallet & POS</div>
                                <div class="mt-2 text-sm leading-6 text-slate-300">Prepaid balance, barcode-first cashier flow, sales ledger, and cashier summaries.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
