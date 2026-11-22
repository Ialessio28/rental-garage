<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record of the admin moving a rental's end date later.
 */
class Extension extends Model
{
    protected $fillable = ['rental_id', 'old_end_date', 'new_end_date', 'extra_cost', 'payment_proof'];

    /**
     * Attribute casts.
     */
    protected function casts(): array
    {
        return [
            'old_end_date' => 'date',
            'new_end_date' => 'date',
        ];
    }

    /**
     * The rental that was extended.
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
