<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Researcher extends Model
{
    use HasFactory;

    protected $table = 'researchers';
    protected $fillable = ['orcid', 'ecris', 'scopusid', 'name', 
                            'email', 'faculty', 'department', 'search_index', 'link'];
}
