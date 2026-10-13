<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'description'];
    
    public function teacher()        { return $this->belongsTo(User::class, 'user_id'); }
    
    public function questions()      { return $this->hasMany(Question::class)->orderBy('position'); }
    
    public function participations() { return $this->hasMany(Participation::class); }
}
