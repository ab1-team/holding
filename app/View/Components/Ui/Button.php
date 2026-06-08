<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;
use Illuminate\View\View;

class Button extends Component
{
    public function __construct(
        public string $variant = 'filled',
        public string $size = 'md',
        public ?string $href = null,
        public string $type = 'button',
        public bool $disabled = false,
        public string $icon = '',
        public string $iconPosition = 'left',
    ) {}

    public function variantClasses(): string
    {
        return match ($this->variant) {
            'filled' => 'bg-primary text-on-primary shadow-elevated hover:bg-indigo-700',
            'tonal' => 'bg-primary-container text-on-primary-container hover:bg-indigo-200',
            'outlined' => 'border border-outline bg-surface-container-lowest text-primary hover:bg-primary-container',
            'text' => 'bg-transparent text-primary hover:bg-primary-container',
            'danger' => 'bg-error text-on-error shadow-elevated hover:bg-rose-700',
            'danger-outlined' => 'border border-error-container bg-surface-container-lowest text-error hover:bg-error-container',
            default => 'bg-primary text-on-primary shadow-elevated hover:bg-indigo-700',
        };
    }

    public function sizeClasses(): string
    {
        return match ($this->size) {
            'sm' => 'h-9 px-3.5 text-xs',
            'md' => 'h-10 px-5 text-sm',
            'lg' => 'h-12 px-6 text-base',
            default => 'h-10 px-5 text-sm',
        };
    }

    public function iconSize(): string
    {
        return match ($this->size) {
            'sm' => 'h-4 w-4',
            'md' => 'h-5 w-5',
            'lg' => 'h-5 w-5',
            default => 'h-5 w-5',
        };
    }

    public function render(): View
    {
        return view('components.ui.button');
    }
}
