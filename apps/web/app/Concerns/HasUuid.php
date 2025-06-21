<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

trait HasUuid
{
    use HasUuids;

    public function uniqueIds()
    {
        return [
            'id',
        ];
    }
}
