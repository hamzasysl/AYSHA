<?php

namespace App\Models\Concerns;

use App\Models\Note;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasNotes
{
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->with('author')->latest()->latest('id');
    }

    /** Kayıt silinirken notları da sil (sahipsiz not panoda 404 veriyor). */
    public function deleteNotes(): void
    {
        Note::where('notable_type', $this->getMorphClass())->where('notable_id', $this->getKey())->delete();
    }
}
