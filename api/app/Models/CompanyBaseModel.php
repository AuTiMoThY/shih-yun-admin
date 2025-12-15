<?php
namespace App\Models;

use CodeIgniter\Model;

class CompanyBaseModel extends Model
{
    protected $table         = 'company_base';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'name',
        'copyright',
        'phone',
        'fax',
        'email',
        'case_email',
        'zipcode',
        'city',
        'district',
        'address',
        'fb_url',
        'yt_url',
        'line_url',
        'keywords',
        'description',
        'head_code',
        'body_code',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
