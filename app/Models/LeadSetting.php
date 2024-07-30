<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadSetting extends Model
{

    use HasFactory, HasCompany;

    protected $table = 'lead_setting';

}
