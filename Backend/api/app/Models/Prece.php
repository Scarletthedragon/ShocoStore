<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prece extends Model
{
    protected $table = 'Prece';

    protected $primaryKey = 'Prece_ID';

    public $timestamps = false;

    protected $fillable = [
        'Nosaukums',
        'Cena',
        'Atlikums',
        'Apraksts',
        'Tonis',
        'Birka',
    ];

    protected function casts(): array
    {
        return [
            'Cena' => 'decimal:2',
            'Atlikums' => 'integer',
        ];
    }

    public function pasutijumi(): HasMany
    {
        return $this->hasMany(Pasutijums::class, 'Prece_ID', 'Prece_ID');
    }
}