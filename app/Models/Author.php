<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{

	protected $table   = 'authors';
	protected $guarded = ['id'];

	public function profession()
	{
		return $this->belongsTo('App\Models\Profession');
	}

	public function nationality()
	{
		return $this->belongsTo('App\Models\Nationality');
	}
	
	public function quotes()
	{
		return $this->hasMany('App\Models\Quote');
	}
	
	public function related_authors()
	{
		return $this->belongsToMany('App\Models\Author', 'related_authors', 'author_1_id', 'author_2_id');
	}
	
	public function site()
	{
		return $this->belongsTo('App\Models\Site');
	}
}