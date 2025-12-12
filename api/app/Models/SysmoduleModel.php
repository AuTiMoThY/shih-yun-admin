<?php
namespace App\Models;

use CodeIgniter\Model;

class SysmoduleModel extends Model
{
    protected $table = 'sysmodule';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'label',
        'name',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}

