<?php

namespace App\Models;

use CodeIgniter\Model;

class PasswordResetModel extends Model
{
    protected $table            = 'password_resets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['email', 'token_hash', 'created_at', 'expires_at'];

    /**
     * Create a new password reset token for the given email.
     * Returns the unhashed token to be provided to the user.
     */
    public function createToken(string $email): string
    {
        $this->cleanupExpired();
        // Remove prior tokens for this email
        $this->where('email', $email)->delete();

        $rawToken = bin2hex(random_bytes(32));
        $hash     = hash('sha256', $rawToken);
        $now      = date('Y-m-d H:i:s');
        $expires  = date('Y-m-d H:i:s', time() + 3600); // 1 hour validity

        $this->insert([
            'email'      => strtolower(trim($email)),
            'token_hash' => $hash,
            'created_at' => $now,
            'expires_at' => $expires,
        ]);

        return $rawToken;
    }

    /**
     * Verify a raw token and return the record if valid and unexpired.
     */
    public function verifyToken(string $rawToken): ?array
    {
        $hash = hash('sha256', trim($rawToken));

        return $this->where('token_hash', $hash)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->first();
    }

    /**
     * Invalidate a token after use.
     */
    public function deleteToken(string $rawToken): void
    {
        $hash = hash('sha256', trim($rawToken));
        $this->where('token_hash', $hash)->delete();
    }

    public function cleanupExpired(): void
    {
        $this->where('expires_at <', date('Y-m-d H:i:s'))->delete();
    }
}

