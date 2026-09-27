<?php

namespace App\Models;

use CodeIgniter\Model;

class NewsletterModel extends Model
{
    protected $table            = 'newsletter_subscribers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['email', 'status'];
    protected $useTimestamps    = true;
    protected $updatedField     = '';

    public function subscribe(string $email): bool
    {
        $email    = strtolower(trim($email));
        $existing = $this->where('email', $email)->first();
        if ($existing) {
            if ($existing['status'] === 'unsubscribed') {
                $this->update($existing['id'], ['status' => 'subscribed']);
            }

            return true;
        }

        return (bool) $this->insert(['email' => $email, 'status' => 'subscribed']);
    }
}
