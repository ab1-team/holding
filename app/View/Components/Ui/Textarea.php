<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;
use Illuminate\View\View;

class Textarea extends Component
{
    public function __construct(
        public string $name,
        public ?string $label = null,
        public mixed $value = null,
        public int $rows = 3,
        public ?string $placeholder = null,
        public bool $required = false,
        public ?string $hint = null,
    ) {}

    public function resolvedValue(): string
    {
        return (string) old($this->name, $this->value ?? '');
    }

    public function hasError(): bool
    {
        return session('errors')?->has($this->name) ?? false;
    }

    public function errorMessage(): ?string
    {
        return session('errors')?->first($this->name);
    }

    public function render(): View
    {
        return view('components.ui.textarea');
    }
}
