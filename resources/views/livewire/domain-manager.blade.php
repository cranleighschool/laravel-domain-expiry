<div>
    <div class="section-header">
        <h2>Manage Domains</h2>
    </div>

    {{-- Add domain form --}}
    <form wire:submit="add" class="add-domain-form">
        <div class="form-row">
            <div class="form-field">
                <input
                    type="text"
                    wire:model="newDomain"
                    placeholder="example.com"
                    class="form-input"
                    autocomplete="off"
                    spellcheck="false"
                >
                @error('newDomain')
                <span class="field-error">{{ $message }}</span>
                @enderror
            </div>
            <div class="form-field">
                <input
                    type="text"
                    wire:model="newNotes"
                    placeholder="Notes (optional)"
                    class="form-input"
                    autocomplete="off"
                >
                @error('newNotes')
                <span class="field-error">{{ $message }}</span>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary">+ Add Domain</button>
        </div>
    </form>

    {{-- Domain list --}}
    @if ($domains->isEmpty())
        <p class="empty-state">No domains configured. Add one above.</p>
    @else
        <table class="manage-table">
            <thead>
            <tr>
                <th>Domain</th>
                <th>Notes</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($domains as $domain)
                <tr>
                    <td class="domain-name">{{ $domain->domain }}</td>
                    <td class="expiry-date">{{ $domain->notes ?: '—' }}</td>
                    <td>
                        <button
                            type="button"
                            wire:click="remove({{ $domain->id }})"
                            wire:confirm="Remove {{ $domain->domain }} from monitoring?"
                            class="remove-btn"
                        >✕ remove
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif


</div>
