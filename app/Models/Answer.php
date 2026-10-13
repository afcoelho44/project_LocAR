<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = ['activity_id', 'object_3d_id', 'description', 'clue_text', 'lat', 'lng', 'position', 'radius'];
    
    public function activity() { return $this->belongsTo(Activity::class); }
    
    public function object3d() { return $this->belongsTo(Object3d::class, 'object_3d_id'); }
    
    public function answers()  { return $this->hasMany(Answer::class); }
}
