<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jabatan extends Model
{
    protected $table = 'jabatan';

    protected $fillable = [
        'nama',
    ];

    public function instansi(): BelongsToMany
    {
        return $this->belongsToMany(Instansi::class, 'instansi_jabatan');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
