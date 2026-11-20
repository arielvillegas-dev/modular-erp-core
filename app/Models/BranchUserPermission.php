<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchUserPermission extends Model
{
    protected $fillable = ['user_id', 'branch_id', 'permission'];
}
