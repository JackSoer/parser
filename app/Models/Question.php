<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $table = 'questions';
    protected $fillable = ['text'];

    public function answers()
    {
        return $this->belongsToMany(Answer::class, 'question_answer', 'question_id', 'answer_id');
    }
}
