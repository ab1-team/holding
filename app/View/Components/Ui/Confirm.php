<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;
use Illuminate\View\View;

class Confirm extends Component
{
    public function __construct(
        public string $id,
        public ?string $title = null,
        public ?string $message = null,
        public string $confirmLabel = 'Konfirmasi',
        public string $cancelLabel = 'Batal',
        public string $variant = 'danger',
        public ?string $action = null,
        public string $method = 'POST',
    ) {}

    public function buttonClasses(): string
    {
        return $this->variant === 'danger'
            ? 'bg-error text-on-error hover:bg-rose-700 shadow-elevated'
            : 'bg-primary text-on-primary hover:bg-indigo-700 shadow-elevated';
    }

    public function render(): View
    {
        return view('components.ui.confirm');
    }
}
