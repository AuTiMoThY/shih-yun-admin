<?php
namespace App\Models;

use CodeIgniter\Model;

class SysModuleModel extends Model
{
    protected $table = 'sys_module';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'label',
        'name',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}

