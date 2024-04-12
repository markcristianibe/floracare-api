<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPlant extends Model
{
    use HasFactory;

    protected $primaryKey = 'plant_id';

    public function reminders()
    {
        return $this->hasMany(Reminder::class, 'plant_id', 'plant_id');
    }
}
