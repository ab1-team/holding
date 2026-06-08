<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;
use Illuminate\View\View;

class AlertDialog extends Component
{
    public function __construct(
        public string $id,
        public ?string $title = null,
        public ?string $message = null,
        public string $okLabel = 'OK',
        public string $variant = 'info',
    ) {}

    public function iconBg(): string
    {
        return match ($this->variant) {
            'success' => 'bg-emerald-100 text-emerald-700',
            'warning' => 'bg-tertiary-container text-on-tertiary-container',
            'error' => 'bg-error-container text-error',
            default => 'bg-primary-container text-primary',
        };
    }

    public function iconName(): string
    {
        return match ($this->variant) {
            'success' => 'check-circle',
            'warning' => 'exclamation',
            'error' => 'x-circle',
            default => 'info',
        };
    }

    public function render(): View
    {
        return view('components.ui.alert-dialog');
    }
}
