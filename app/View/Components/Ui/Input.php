<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;
use Illuminate\View\View;

class Input extends Component
{
    public function __construct(
        public string $name,
        public ?string $label = null,
        public string $type = 'text',
        public mixed $value = null,
        public ?string $placeholder = null,
        public bool $required = false,
        public ?string $hint = null,
        public ?string $leadingIcon = null,
        public ?string $trailingIcon = null,
        public ?string $min = null,
        public ?string $max = null,
        public ?string $step = null,
    ) {}

    public function resolvedValue(): ?string
    {
        if ($this->type === 'password') {
            return null;
        }
        $old = old($this->name, $this->value);
        return $old === null ? null : (string) $old;
    }

    public function isNumeric(): bool
    {
        return in_array($this->type, ['number', 'integer'], true);
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
        return view('components.ui.input');
    }
}
