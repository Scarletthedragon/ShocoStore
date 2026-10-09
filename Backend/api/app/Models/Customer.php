<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    use HasFactory;

    protected $table = 'Lietotajs';

    protected $primaryKey = 'Lietotajs_ID';

    public $timestamps = false;

    protected $fillable = ['Vards', 'Epasts', 'Parole'];

    protected $hidden = ['Parole'];

    protected function casts(): array
    {
        return ['Parole' => 'hashed'];
    }

    public function getAuthPasswordName(): string
    {
        return 'Parole';
    }
}
