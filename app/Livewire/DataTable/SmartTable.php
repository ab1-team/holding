<?php

namespace App\Livewire\DataTable;

use Illuminate\Pagination\LengthAwarePaginator;
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

    #[Url(except: 1)]
    public int $page = 1;

    /** @var array<int, string> */
    public array $searchableColumns = [];

    /** @var array<string, mixed> */
    public array $extraFilters = [];

    /** @var array<int, string> */
    public array $withRelations = [];

    /** @var array<int, array{label:string,field?:string,view?:string,align?:string}> */
    public array $columns = [];

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
        array $searchableColumns = [],
        array $columns = [],
        array $withRelations = [],
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
        $this->searchableColumns = $searchableColumns;
        $this->columns = $columns;
        $this->withRelations = $withRelations;
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->page = 1;
    }

    public function goToPage(int $p): void
    {
        $this->page = max(1, $p);
    }

    public function getItemsProperty(): LengthAwarePaginator
    {
        $modelClass = $this->model;
        if (! class_exists($modelClass)) {
            return new LengthAwarePaginator([], 0, $this->perPage, $this->page);
        }
        /** @var Builder $query */
        $query = $modelClass::query();
        if (! empty($this->withRelations)) {
            $query->with($this->withRelations);
        }

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $columns = $this->searchableColumns;
            if (empty($columns)) {
                $columns = array_filter(
                    \Schema::getColumnListing($modelClass::query()->getModel()->getTable()),
                    fn (string $col) => in_array($col, ['name', 'title', 'slug', 'email', 'base_url', 'description', 'phone'])
                );
                $columns = array_values($columns);
            }
            if (! empty($columns)) {
                $query->where(function (Builder $q) use ($term, $columns) {
                    foreach ($columns as $col) {
                        $q->orWhere($col, 'like', $term);
                    }
                });
            }
        }

        foreach ($this->extraFilters as $key => $value) {
            if ($value !== null && $value !== '') {
                $query->where($key, $value);
            }
        }

        $total = $query->count();
        $items = $query->orderBy('id', 'desc')
            ->forPage($this->page, $this->perPage)
            ->get();

        return new LengthAwarePaginator($items, $total, $this->perPage, $this->page);
    }

    public function render()
    {
        return view('livewire.data-table.smart-table', [
            'items' => $this->items,
        ]);
    }
}
