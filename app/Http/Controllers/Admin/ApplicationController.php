<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.applications.index', [
            'searchPlaceholder' => 'Cari nama, slug, atau base url...',
            'empty' => 'Belum ada aplikasi. Tambah aplikasi pertama Anda.',
            'initialSearch' => $request->query('search', ''),
        ]);
    }

    private function tableColumns(): array
    {
        $cubeIcon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>';

        return [
            ['label' => 'Aplikasi', 'format' => function ($a) use ($cubeIcon) {
                return '<div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-container text-on-primary-container">' . $cubeIcon . '</div><div><div class="text-sm font-semibold text-on-surface">' . e($a->name) . '</div><div class="text-xs text-on-surface-variant"><code class="rounded bg-surface-container px-1.5 py-0.5">' . e($a->slug) . '</code></div></div></div>';
            }],
            ['label' => 'Base URL', 'format' => fn($a) => '<a href="' . e($a->base_url) . '" target="_blank" rel="noopener" class="font-medium text-primary hover:underline">' . e(parse_url($a->base_url, PHP_URL_HOST)) . '</a>'],
            ['label' => 'Laporan', 'format' => fn($a) => '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ' . ($a->has_financial_report ? 'bg-sky-100 text-sky-800' : 'bg-surface-container text-on-surface-variant') . '">' . ($a->has_financial_report ? 'Aktif' : 'Tidak') . '</span>'],
            ['label' => 'Status', 'format' => fn($a) => '<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ' . ($a->is_active ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant') . '"><span class="h-1.5 w-1.5 rounded-full ' . ($a->is_active ? 'bg-secondary' : 'bg-on-surface-variant') . '"></span>' . ($a->is_active ? 'Aktif' : 'Nonaktif') . '</span>'],
            ['label' => 'Aksi', 'align' => 'right', 'view' => 'admin.applications._cell_actions'],
        ];
    }

    public function create(): View
    {
        return view('admin.applications.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['api_token_key'] = Str::random(32);

        $application = Application::create($data);

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('status', "Aplikasi {$application->name} berhasil ditambahkan. Salin api_token_key sekarang — tidak akan ditampilkan lagi.");
    }

    public function show(Application $application): View
    {
        return view('admin.applications.show', [
            'application' => $application,
        ]);
    }

    public function edit(Application $application): View
    {
        return view('admin.applications.edit', [
            'application' => $application,
        ]);
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        $data = $this->validateData($request, $application->id);
        $application->update($data);

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('status', "Aplikasi {$application->name} berhasil diperbarui.");
    }

    public function destroy(Application $application): RedirectResponse
    {
        $name = $application->name;
        $application->delete();

        return redirect()
            ->route('admin.applications.index')
            ->with('status', "Aplikasi {$name} berhasil dihapus.");
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $slugRule = ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/'];
        $slugRule[] = $ignoreId
            ? 'unique:applications,slug,' . $ignoreId
            : 'unique:applications,slug';

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => $slugRule,
            'description' => ['nullable', 'string'],
            'icon_path' => ['nullable', 'string', 'max:255'],
            'base_url' => ['required', 'url', 'max:255'],
            'has_financial_report' => ['boolean'],
            'is_active' => ['boolean'],
        ]);
    }
}
