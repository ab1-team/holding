<?php

namespace App\Livewire\Form;

use Livewire\Attributes\Modelable;
use Livewire\Component;

class SmartSelect extends Component
{
    #[Modelable]
    public mixed $value = null;

    public string $name = '';
    public ?string $label = null;
    public ?string $placeholder = 'Pilih...';
    public ?string $searchPlaceholder = 'Cari...';
    public ?string $hint = null;
    public bool $required = false;
    public bool $searchable = true;
    public bool $clearable = true;
    public ?string $error = null;

    /** @var array<string, string>|array<int, string> */
    public array $options = [];

    public function mount(
        string $name,
        array $options = [],
        ?string $label = null,
        ?string $placeholder = 'Pilih...',
        ?string $searchPlaceholder = 'Cari...',
        ?string $hint = null,
        bool $required = false,
        bool $searchable = true,
        bool $clearable = true,
    ): void {
        $this->name = $name;
        $this->options = $options;
        $this->label = $label;
        $this->placeholder = $placeholder;
        $this->searchPlaceholder = $searchPlaceholder;
        $this->hint = $hint;
        $this->required = $required;
        $this->searchable = $searchable;
        $this->clearable = $clearable;
    }

    public function select(mixed $key): void
    {
        $this->value = (string) $key;
        $this->dispatch('smart-select-updated', name: $this->name, value: $this->value);
    }

    public function clear(): void
    {
        $this->value = null;
        $this->dispatch('smart-select-updated', name: $this->name, value: null);
    }

    public function getSelectedLabelProperty(): ?string
    {
        $val = (string) ($this->value ?? '');
        if ($val === '') return null;
        return $this->options[$val] ?? $this->options[(int) $val] ?? null;
    }

    public function render()
    {
        return view('livewire.form.smart-select');
    }
}