<div class="flex items-center gap-3">
    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-container text-sm font-semibold text-on-primary-container">{{ strtoupper(mb_substr($row->name, 0, 1)) }}</div>
    <div class="min-w-0">
        <div class="truncate text-sm font-semibold text-on-surface">{{ $row->name }}</div>
        <div class="truncate text-xs text-on-surface-variant">{{ $row->email }}</div>
    </div>
</div>
