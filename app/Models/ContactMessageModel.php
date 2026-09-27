<?php

namespace App\Models;

use CodeIgniter\Model;

class ContactMessageModel extends Model
{
    protected $table            = 'contact_messages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['name', 'email', 'phone', 'subject', 'message', 'status'];
    protected $useTimestamps    = true;
    protected $updatedField     = '';
    protected $validationRules  = [
        'name'    => 'required|max_length[120]',
        'email'   => 'required|valid_email|max_length[190]',
        'message' => 'required|max_length[3000]',
    ];
}
