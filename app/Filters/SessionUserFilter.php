<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Re-checks the signed-in user on every request. The session only stores the
 * user's id and role from login, so without this a deleted or suspended
 * account (admins included) stayed signed in until the session expired, and
 * pages expecting the user crashed. Such sessions are signed out here; for
 * live accounts, name/email/role are refreshed so changes apply immediately.
 * Runs before AuthFilter/AdminFilter, which then treat the visitor as a guest.
 */
class SessionUserFilter implements FilterInterface
{
    private const AUTH_KEYS = ['user_id', 'user_name', 'user_email', 'user_role', 'cart_count'];

    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $userId  = (int) $session->get('user_id');
        if ($userId === 0) {
            return;
        }

        $user = db_connect()->table('users')
            ->select('first_name, last_name, email, role, status, deleted_at')
            ->where('id', $userId)->get()->getRowArray();

        if (! $user || $user['deleted_at'] !== null || $user['status'] !== 'active') {
            $session->remove(self::AUTH_KEYS);
            $session->regenerate(true);

            return;
        }

        $fresh = [
            'user_name'  => trim($user['first_name'] . ' ' . ($user['last_name'] ?? '')),
            'user_email' => $user['email'],
            'user_role'  => $user['role'],
        ];
        foreach ($fresh as $key => $value) {
            if ($session->get($key) !== $value) {
                $session->set($key, $value);
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
