<?php

namespace App\View\Components\DataTable;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\Component;
use Illuminate\View\View;

class SmartTable extends Component
{
    public function __construct(
        public LengthAwarePaginator $items,
        public array $columns = [],
        public ?string $search = null,
        public string $searchPlaceholder = 'Cari...',
        public bool $searchable = true,
        public array $perPageOptions = [10, 15, 25, 50, 100],
        public ?string $empty = 'Belum ada data.',
        public ?string $title = null,
        public ?string $subtitle = null,
        public ?string $overline = null,
        public ?Closure $rowActions = null,
    ) {}

    public function render(): View
    {
        return view('components.data-table.smart-table');
    }
}
