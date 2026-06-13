<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;
use Illuminate\View\View;

class EmptyState extends Component
{
    public function __construct(
        public string $icon = 'inbox',
        public ?string $title = null,
        public ?string $description = null,
    ) {}

    public function render(): View
    {
        return view('components.ui.empty-state');
    }
}
