<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;
use Illuminate\View\View;

class Badge extends Component
{
    public function __construct(
        public string $variant = 'neutral',
        public ?string $icon = null,
        public string $size = 'md',
    ) {}

    public function variantClasses(): string
    {
        return match ($this->variant) {
            'success' => 'bg-secondary-container text-on-secondary-container',
            'warning' => 'bg-tertiary-container text-on-tertiary-container',
            'error' => 'bg-error-container text-on-error-container',
            'info' => 'bg-primary-container text-on-primary-container',
            'neutral' => 'bg-surface-container text-on-surface-variant',
            'outline' => 'border border-outline-variant text-on-surface',
            default => 'bg-surface-container text-on-surface-variant',
        };
    }

    public function sizeClasses(): string
    {
        return match ($this->size) {
            'sm' => 'px-2 py-0.5 text-[10px]',
            'md' => 'px-2.5 py-0.5 text-xs',
            default => 'px-2.5 py-0.5 text-xs',
        };
    }

    public function render(): View
    {
        return view('components.ui.badge');
    }
}
