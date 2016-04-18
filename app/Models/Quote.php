<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{

	protected $table   = 'quotes';
	protected $guarded = ['id'];

	public function author()
	{
		return $this->belongsTo('App\Models\Author');
	}
	
	public function keywords()
	{
		return $this->belongsToMany('App\Models\Keyword', 'quote_keywords', 'id_quote', 'id_keyword');
	}
	
	public function topics()
	{
		return $this->belongsToMany('App\Models\Topic', 'quote_topics', 'id_quote', 'id_topic');
	}
}