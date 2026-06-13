<code class="rounded bg-surface-container px-1.5 py-0.5 text-xs">{{ $row->subject_type ? class_basename($row->subject_type) : '—' }}#{{ $row->subject_id ?? '—' }}</code>
