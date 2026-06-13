<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;
use Illuminate\View\View;

class PageHeader extends Component
{
    public function __construct(
        public ?string $overline = null,
        public ?string $title = null,
        public ?string $subtitle = null,
        public bool $centered = false,
    ) {}

    public function render(): View
    {
        return view('components.ui.page-header');
    }
}
