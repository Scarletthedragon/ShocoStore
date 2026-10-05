<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pasutijums extends Model
{
    protected $table = 'Pasutijums';

    protected $primaryKey = 'Pasutijums_ID';

    public $timestamps = false;

    protected $fillable = [
        'Lietotajs_ID',
        'Prece_ID',
        'Daudzums',
        'Kopeja_cena',
        'Statuss',
        'Klienta_vards',
    ];

    protected function casts(): array
    {
        return [
            'Daudzums' => 'integer',
            'Kopeja_cena' => 'decimal:2',
            'Izveidots' => 'datetime',
        ];
    }

    public function prece(): BelongsTo
    {
        return $this->belongsTo(Prece::class, 'Prece_ID', 'Prece_ID');
    }
}