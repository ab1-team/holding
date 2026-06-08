<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;
use Illuminate\View\View;

class Dropdown extends Component
{
    public function __construct(
        public string $align = 'left',
        public string $width = 'w-56',
    ) {}

    public function alignmentClasses(): string
    {
        return match ($this->align) {
            'right' => 'right-0 origin-top-right',
            default => 'left-0 origin-top-left',
        };
    }

    public function render(): View
    {
        return view('components.ui.dropdown');
    }
}
