<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeywordRange extends Model
{
	protected $table   = 'keyword_ranges';
	protected $guarded = ['id'];
	
	public function keywords()
	{
		return $this->hasMany('App\Models\Keyword');
	}
	
	public function site()
	{
		return $this->belongsTo('App\Models\Nationality');
	}
}
