<x-ui.entity-cell
    icon="cube"
    variant="primary"
    :name="$row->name"
    :subtitleRaw="'<code class=\'rounded bg-surface-container px-1.5 py-0.5\'>'.$row->slug.'</code>'"
/>
