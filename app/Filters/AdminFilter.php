<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Restricts access to admin users only (§40 authorization checks).
 */
class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        if (! $session->get('user_id')) {
            $session->set('redirect_url', current_url());

            return redirect()->to(site_url('login'))->with('info', 'Please sign in to continue.');
        }
        if ($session->get('user_role') !== 'admin') {
            return service('response')->setStatusCode(403)
                ->setBody(view('errors/custom/403'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
