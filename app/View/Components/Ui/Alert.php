<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;
use Illuminate\View\View;

class Alert extends Component
{
    public function __construct(
        public string $variant = 'info',
        public ?string $title = null,
        public string $icon = '',
           public bool $dismissible = false,
    ) {}

    public function containerClasses(): string
    {
        return match ($this->variant) {
            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
            'warning' => 'border-tertiary-container bg-tertiary-container/30 text-on-tertiary-container',
            'error' => 'border-error-container bg-error-container/30 text-on-error-container',
            'info' => 'bg-primary-container text-on-primary-container',
            default => 'border-outline bg-surface-container text-on-surface',
        };
    }

    public function iconName(): string
    {
        if ($this->icon) {
            return $this->icon;
        }
        return match ($this->variant) {
            'success' => 'check-circle',
            'warning' => 'exclamation',
            'error' => 'x-circle',
            default => 'info',
        };
    }

    public function render(): View
    {
        return view('components.ui.alert');
    }
}
