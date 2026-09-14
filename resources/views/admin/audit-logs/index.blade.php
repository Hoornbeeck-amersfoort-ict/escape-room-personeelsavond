<x-layouts.admin title="Audit log">
    <h1 class="mb-6 text-2xl font-bold text-slate-900">Audit log</h1>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Datum</th>
                    <th class="px-4 py-3">Admin</th>
                    <th class="px-4 py-3">Actie</th>
                    <th class="px-4 py-3">Doel</th>
                    <th class="px-4 py-3">Oud</th>
                    <th class="px-4 py-3">Nieuw</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $log->created_at->format('d-m-Y H:i:s') }}</td>
                        <td class="px-4 py-3">{{ $log->admin?->name ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $log->action }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ class_basename($log->target_type) }} #{{ $log->target_id }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $log->old_values ? json_encode($log->old_values) : '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $log->new_values ? json_encode($log->new_values) : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">Nog geen handmatige acties gelogd.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</x-layouts.admin>
