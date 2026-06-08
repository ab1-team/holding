<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;
use Illuminate\View\View;

class Modal extends Component
{
    public function __construct(
        public string $id,
        public ?string $title = null,
        public string $size = 'md',
        public ?string $subtitle = null,
    ) {}

    public function sizeClasses(): string
    {
        return match ($this->size) {
            'sm' => 'max-w-sm',
            'md' => 'max-w-md',
            'lg' => 'max-w-lg',
            'xl' => 'max-w-2xl',
            default => 'max-w-md',
        };
    }

    public function render(): View
    {
        return view('components.ui.modal');
    }
}
