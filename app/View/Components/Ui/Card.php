<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;
use Illuminate\View\View;

class Card extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $subtitle = null,
        public ?string $overline = null,
        public bool $padded = true,
    ) {}

    public function render(): View
    {
        return view('components.ui.card');
    }
}
