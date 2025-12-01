<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Analysis extends Model {
    protected $fillable = ['as_of_date','title','notes','file_path','tags'];
    protected $casts = ['as_of_date'=>'date','tags'=>'array'];
}
