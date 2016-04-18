<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
	protected $table   = 'keywords';
	protected $guarded = ['id'];
	
	public function quotes()
	{
		return $this->belongsToMany('App\Models\Quote', 'quote_keywords', 'id_keyword', 'id_quote');
	}
	
	public function keywordRange()
	{
		return $this->belongsTo('App\Models\KeywordRange');
	}
	
	public function site()
	{
		return $this->belongsTo('App\Models\Site');
	}
}