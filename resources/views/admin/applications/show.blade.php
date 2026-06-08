@extends('layouts.app')

@section('title', "{$application->name} — Holding App")

@section('content')

    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $application->name }}</h1>
            <p class="mt-1 text-sm text-slate-500"><code class="text-xs">{{ $application->slug }}</code></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.applications.edit', $application) }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Edit</a>
            <form method="POST" action="{{ route('admin.applications.destroy', $application) }}" onsubmit="return confirm('Hapus aplikasi {{ $application->name }}? Tenant yang terikat akan kehilangan akses.')">
                @csrf @method('DELETE')
                <button type="submit" class="rounded-md border border-rose-300 bg-white px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">Hapus</button>
            </form>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm lg:col-span-2">
            <div class="border-b border-slate-200 px-5 py-3"><h2 class="text-sm font-semibold text-slate-900">Detail Aplikasi</h2></div>
            <div class="px-5 py-4">
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <dt class="text-sm text-slate-500">Deskripsi</dt>
                    <dd class="sm:col-span-2 text-sm text-slate-900">{{ $application->description ?: '—' }}</dd>

                    <dt class="text-sm text-slate-500">Base URL</dt>
                    <dd class="sm:col-span-2 text-sm"><a href="{{ $application->base_url }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">{{ $application->base_url }}</a></dd>

                    <dt class="text-sm text-slate-500">Path Ikon</dt>
                    <dd class="sm:col-span-2 text-sm text-slate-900"><code class="text-xs">{{ $application->icon_path ?: '—' }}</code></dd>

                    <dt class="text-sm text-slate-500">Laporan Keuangan</dt>
                    <dd class="sm:col-span-2 text-sm">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $application->has_financial_report ? 'bg-sky-100 text-sky-800' : 'bg-slate-100 text-slate-700' }}">
                            {{ $application->has_financial_report ? 'Didukung' : 'Tidak Didukung' }}
                        </span>
                    </dd>

                    <dt class="text-sm text-slate-500">Status</dt>
                    <dd class="sm:col-span-2 text-sm">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $application->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                            {{ $application->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-3"><h2 class="text-sm font-semibold text-slate-900">API Token Key</h2></div>
            <div class="px-5 py-4">
                <p class="mb-2 text-xs text-slate-500">Digunakan subsidiary untuk memvalidasi request dari Holding App.</p>
                <div class="flex gap-2">
                    <input type="text" id="tokenInput" value="{{ $application->api_token_key }}" readonly class="block w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 font-mono text-xs">
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('tokenInput').value)" class="shrink-0 rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">Salin</button>
                </div>
            </div>
        </div>
    </div>
@endsection
