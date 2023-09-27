<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $table = 'answers';
    protected $fillable = ['text'];

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'question_answer', 'answer_id', 'question_id');
    }
}
