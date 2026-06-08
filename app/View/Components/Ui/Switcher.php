<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;
use Illuminate\View\View;

class Switcher extends Component
{
    public function __construct(
        public string $name,
        public ?string $label = null,
        public ?string $description = null,
        public bool $checked = false,
        public string $onValue = '1',
        public string $offValue = '0',
        public string $size = 'md',
    ) {}

    public function isChecked(): bool
    {
        return (bool) old($this->name, $this->checked);
    }

    public function trackClasses(): string
    {
        return $this->isChecked()
            ? 'bg-primary'
            : 'bg-outline';
    }

    public function thumbClasses(): string
    {
        $size = match ($this->size) {
            'sm' => 'h-4 w-4',
            'lg' => 'h-6 w-6',
            default => 'h-5 w-5',
        };
        $translate = $this->isChecked()
            ? ($this->size === 'sm' ? 'translate-x-4' : ($this->size === 'lg' ? 'translate-x-7' : 'translate-x-5'))
            : 'translate-x-0.5';
        return "{$size} {$translate} transition-transform";
    }

    public function trackSize(): string
    {
        return match ($this->size) {
            'sm' => 'h-5 w-9',
            'lg' => 'h-8 w-14',
            default => 'h-6 w-11',
        };
    }

    public function render(): View
    {
        return view('components.ui.switcher');
    }
}
