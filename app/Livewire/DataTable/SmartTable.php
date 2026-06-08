<?php

namespace App\Livewire\DataTable;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SmartTable extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    public string $model = '';

    public string $searchPlaceholder = 'Cari...';
    public ?string $title = null;
    public ?string $overline = null;
    public ?string $subtitle = null;
    public string $empty = 'Belum ada data.';
    public string $createUrl = '';
    public string $createLabel = 'Tambah';
    public ?string $createIcon = 'plus';

    /** @var array<int, int> */
    public array $perPageOptions = [10, 15, 25, 50, 100];

    #[Url(except: 15)]
    public int $perPage = 15;

    /** @var array<string, mixed> */
    public array $extraFilters = [];

    public function mount(
        string $model,
        ?string $title = null,
        ?string $overline = null,
        ?string $subtitle = null,
        string $searchPlaceholder = 'Cari...',
        string $empty = 'Belum ada data.',
        string $createUrl = '',
        string $createLabel = 'Tambah',
        ?string $createIcon = 'plus',
        array $perPageOptions = [10, 15, 25, 50, 100],
    ): void {
        $this->model = $model;
        $this->title = $title;
        $this->overline = $overline;
        $this->subtitle = $subtitle;
        $this->searchPlaceholder = $searchPlaceholder;
        $this->empty = $empty;
        $this->createUrl = $createUrl;
        $this->createLabel = $createLabel;
        $this->createIcon = $createIcon;
        $this->perPageOptions = $perPageOptions;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function getItemsProperty(): LengthAwarePaginator
    {
        $modelClass = $this->model;
        if (! class_exists($modelClass)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage);
        }
        /** @var Builder $query */
        $query = $modelClass::query();

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('email', 'like', $term)
                  ->orWhere('slug', 'like', $term)
                  ->orWhere('base_url', 'like', $term);
            });
        }

        foreach ($this->extraFilters as $key => $value) {
            if ($value !== null && $value !== '') {
                $query->where($key, $value);
            }
        }

        return $query->orderBy('id', 'desc')->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.data-table.smart-table', [
            'items' => $this->items,
        ]);
    }
}