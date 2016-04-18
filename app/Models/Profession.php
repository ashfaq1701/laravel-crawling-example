<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profession extends Model
{
	protected $table   = 'professions';
	protected $guarded = ['id'];
	
	public function authors()
	{
		return $this->hasMany('App\Models\Author');
	}
}