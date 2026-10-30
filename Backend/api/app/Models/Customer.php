<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

class Customer extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $table = 'Lietotajs';

    protected $primaryKey = 'Lietotajs_ID';

    public $timestamps = false;

    protected $fillable = ['Vards', 'Epasts', 'Parole'];

    protected $hidden = ['Parole'];

    protected function casts(): array
    {
        return ['Parole' => 'hashed', 'email_verified_at' => 'datetime', 'delivery_address' => 'array'];
    }

    public function getAuthPasswordName(): string
    {
        return 'Parole';
    }

    public function routeNotificationForMail(?Notification $notification = null): string
    {
        return $this->Epasts;
    }
}
