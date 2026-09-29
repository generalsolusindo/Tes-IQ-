<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LokasiKantor extends Model
{
    protected $table = 'lokasi_kantor';

    protected $fillable = [
        'instansi_id',
        'latitude',
        'longitude',
        'radius_meter',
    ];

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class);
    }

    public function distanceInMetersFrom(float $latitude, float $longitude): float
    {
        $earthRadiusMeters = 6_371_000;

        $latitudeDelta = deg2rad($latitude - (float) $this->latitude);
        $longitudeDelta = deg2rad($longitude - (float) $this->longitude);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad((float) $this->latitude)) * cos(deg2rad($latitude))
            * sin($longitudeDelta / 2) ** 2;

        return $earthRadiusMeters * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
