<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Community extends Model
{
    protected $table = 'community';
    protected $fillable = ['owner_id', 'name', 'website', 'bio', 'city', 'uf', 'tags'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}