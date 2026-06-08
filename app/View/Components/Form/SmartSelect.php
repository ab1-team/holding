<?php

namespace App\View\Components\Form;

use Illuminate\View\Component;
use Illuminate\View\View;

class SmartSelect extends Component
{
    public function __construct(
        public string $name,
        public array $options = [],
        public mixed $value = null,
        public ?string $placeholder = 'Pilih...',
        public ?string $searchPlaceholder = 'Cari...',
        public ?string $label = null,
        public bool $required = false,
        public ?string $hint = null,
        public bool $clearable = false,
    ) {}

    public function resolvedValue(): mixed
    {
        return old($this->name, $this->value);
    }

    public function selectedLabel(): ?string
    {
        $v = $this->resolvedValue();
        if ($v === null || $v === '') return null;
        foreach ($this->options as $key => $label) {
            if ((string) $key === (string) $v) return $label;
        }
        return null;
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
        return view('components.form.smart-select');
    }
}
