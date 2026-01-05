<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['user_id', 'type', 'content', 'generated_at'];

    protected $casts = [
        'content' => 'array', // Para que Laravel trate el JSON como array automáticamente
        'generated_at' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
