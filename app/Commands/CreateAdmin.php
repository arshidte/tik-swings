<?php

namespace App\Commands;

use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CreateAdmin extends BaseCommand
{
    protected $group       = 'Auth';
    protected $name        = 'admin:create';
    protected $description = 'Create or promote an administrator account safely.';
    protected $usage       = 'admin:create [email] [password] [options]';
    protected $arguments   = [
        'email'    => 'Administrator email address',
        'password' => 'Administrator password (min 8 characters)',
    ];
    protected $options     = [
        '--name'  => 'Administrator name (defaults to Store Admin)',
        '--phone' => 'Contact phone number',
    ];

    public function run(array $params)
    {
        $email = $params[0] ?? CLI::prompt('Administrator email', null, 'required|valid_email');
        $password = $params[1] ?? CLI::prompt('Administrator password (min 8 chars)', null, 'required|min_length[8]');
        $name = CLI::getOption('name') ?: 'Store Admin';
        $phone = CLI::getOption('phone') ?: '9000000000';

        $users = new UserModel();
        $existing = $users->findByEmail($email);

        if ($existing) {
            $users->update($existing['id'], [
                'role'          => 'admin',
                'status'        => 'active',
                'password_hash' => $users->hashPassword($password),
            ]);
            CLI::write("User {$email} updated/promoted to administrator.", 'green');
            return;
        }

        $users->insert([
            'first_name'        => $name,
            'email'             => strtolower(trim($email)),
            'phone'             => $phone,
            'password_hash'     => $users->hashPassword($password),
            'role'              => 'admin',
            'status'            => 'active',
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);

        CLI::write("Administrator {$email} created successfully.", 'green');
    }
}

