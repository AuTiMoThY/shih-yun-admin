<?php
namespace App\Models;

use CodeIgniter\Model;

class AppNewsModel extends Model
{
    protected $table         = 'app_news';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'title',
        'cover',
        'slide',
        'content',
        'show_date',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
