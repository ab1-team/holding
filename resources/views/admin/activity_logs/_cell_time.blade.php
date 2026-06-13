<a href="{{ route('admin.activity-logs.show', $row) }}" class="font-medium text-primary hover:underline">{{ $row->created_at->translatedFormat('d M Y, H:i') }}</a>
