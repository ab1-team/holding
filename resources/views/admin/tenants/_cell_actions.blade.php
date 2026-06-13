<div class="flex items-center justify-end gap-1">
    <x-ui.button :href="route('admin.tenants.show', $row)" variant="text" size="sm" icon="eye" title="Detail" />
    <x-ui.button :href="route('admin.tenants.edit', $row)" variant="text" size="sm" icon="pencil" title="Edit" />
</div>
