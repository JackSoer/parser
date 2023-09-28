<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $table = 'tasks';
    protected $fillable = ['url', 'status'];

    public function updateStatus($status)
    {
        $this->status = $status;
        $this->save();
    }
}
