<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
	protected $table   = 'topics';
	protected $guarded = ['id'];
	
	public function quotes()
	{
		return $this->belongsToMany('App\Models\Quote', 'quote_topics', 'id_topic', 'id_quote');
	}
}