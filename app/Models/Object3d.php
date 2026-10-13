<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Object3d extends Model
{
    use HasFactory;

    protected $table = 'objects_3d';
    
    protected $fillable = ['name', 'file_path'];

    public function questions() { return $this->hasMany(Question::class, 'object_3d_id'); }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

}
