<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nationality extends Model
{
	protected $table   = 'nationalities';
	protected $guarded = ['id'];
	
	public function authors()
	{
		return $this->hasMany('App\Models\Author');
	}
}