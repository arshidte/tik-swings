<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'first_name', 'last_name', 'email', 'phone', 'password_hash',
        'role', 'status', 'email_verified_at', 'remember_token', 'last_login_at',
    ];
    protected $useTimestamps = true;

    protected $validationRules = [
        'first_name' => 'required|max_length[80]',
        'email'      => 'required|valid_email|max_length[190]|is_unique[users.email,id,{id}]',
    ];
    protected $validationMessages = [
        'email' => ['is_unique' => 'An account with this email already exists.'],
    ];

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', strtolower(trim($email)))->first();
    }

    /**
     * Hash a plaintext password. Never store raw passwords (§40).
     */
    public function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
