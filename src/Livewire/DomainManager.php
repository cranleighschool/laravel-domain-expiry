<?php

declare(strict_types=1);

namespace CranleighSchool\DomainExpiry\Livewire;

use CranleighSchool\DomainExpiry\Models\Domain;
use Illuminate\View\View;
use Livewire\Component;

class DomainManager extends Component
{
    public string $newDomain = '';

    public string $newNotes = '';

    public function add(): void
    {
        $this->validate([
            'newDomain' => ['required', 'string', 'max:253', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}$/'],
            'newNotes' => ['nullable', 'string', 'max:500'],
        ]);

        $domain = strtolower((string) preg_replace('/^www\./i', '', trim($this->newDomain)));

        Domain::query()->firstOrCreate(
            ['domain' => $domain],
            ['notes' => $this->newNotes ?: null, 'active' => true],
        );

        $this->reset('newDomain', 'newNotes');
    }

    public function remove(int $id): void
    {
        Domain::query()->findOrFail($id)->delete();
    }

    public function render(): View
    {
        return view('domain-expiry::livewire.domain-manager', [
            'domains' => Domain::query()->orderBy('domain')->get(),
        ]);
    }
}
