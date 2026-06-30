<?php

namespace App\Models\Concerns;

use App\Support\EncodedId;

trait HasEncodedId
{
    public function getEncodedIdAttribute(): ?string
    {
        return isset($this->attributes[$this->getKeyName()])
            ? EncodedId::encode($this->getKey())
            : null;
    }
}
