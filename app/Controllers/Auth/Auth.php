<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\WishlistModel;
use App\Services\CartService;

class Auth extends BaseController
{
    public function login()
    {
        if (is_logged_in()) {
            return redirect()->to(site_url('account'));
        }

        return view('auth/login', ['meta' => ['title' => 'Sign In — ' . store_name()]]);
    }

    public function attemptLogin()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $users = new UserModel();
        $user  = $users->findByEmail($this->request->getPost('email'));

        if (! $user || ! $users->verifyPassword($this->request->getPost('password'), $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'The email or password is incorrect.');
        }
        if ($user['status'] !== 'active') {
            return redirect()->back()->with('error', 'This account has been suspended.');
        }

        $this->establishSession($user);
        $users->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        $redirect = session()->get('redirect_url') ?? site_url($user['role'] === 'admin' ? 'admin' : 'account');
        session()->remove('redirect_url');

        return redirect()->to($redirect)->with('success', 'Welcome back, ' . esc($user['first_name']) . '.');
    }

    public function register()
    {
        if (is_logged_in()) {
            return redirect()->to(site_url('account'));
        }

        return view('auth/register', ['meta' => ['title' => 'Create Account — ' . store_name()]]);
    }

    public function attemptRegister()
    {
        $rules = [
            'first_name' => 'required|max_length[80]',
            'email'      => 'required|valid_email|is_unique[users.email]',
            'phone'      => 'permit_empty|min_length[10]|max_length[15]',
            'password'   => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];
        $messages = [
            'email'    => ['is_unique' => 'An account with this email already exists.'],
            'password' => ['min_length' => 'Password must be at least 8 characters.'],
            'password_confirm' => ['matches' => 'Passwords do not match.'],
        ];
        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $users = new UserModel();
        $id    = $users->insert([
            'first_name'    => $this->request->getPost('first_name'),
            'last_name'     => $this->request->getPost('last_name'),
            'email'         => strtolower(trim($this->request->getPost('email'))),
            'phone'         => $this->request->getPost('phone'),
            'password_hash' => $users->hashPassword($this->request->getPost('password')),
            'role'          => 'customer',
            'status'        => 'active',
        ]);

        $user = $users->find($id);
        $this->establishSession($user);

        return redirect()->to(site_url('account'))->with('success', 'Welcome to ' . store_name() . '.');
    }

    public function forgot()
    {
        return view('auth/forgot', ['meta' => ['title' => 'Reset Password — ' . store_name()]]);
    }

    public function sendReset()
    {
        // Password-reset email delivery is environment-specific; we always show a
        // neutral confirmation to avoid leaking which emails are registered.
        return redirect()->to(site_url('forgot-password'))
            ->with('success', 'If that email is registered, we have sent reset instructions.');
    }

    public function logout()
    {
        session()->remove(['user_id', 'user_name', 'user_email', 'user_role', 'cart_count']);
        session()->destroy();

        return redirect()->to(site_url('/'))->with('success', 'You have been signed out.');
    }

    /**
     * Set the session and merge any guest cart / wishlist into the account (§29).
     */
    private function establishSession(array $user): void
    {
        session()->set([
            'user_id'    => $user['id'],
            'user_name'  => trim($user['first_name'] . ' ' . ($user['last_name'] ?? '')),
            'user_email' => $user['email'],
            'user_role'  => $user['role'],
        ]);

        (new CartService())->mergeGuestIntoUser((int) $user['id']);
        (new CartService())->syncCount();

        // Merge guest wishlist
        $guestWish = session()->get('wishlist') ?? [];
        if ($guestWish) {
            $wl       = new WishlistModel();
            $existing = array_map('intval', $wl->productIdsForUser((int) $user['id']));
            foreach ($guestWish as $pid) {
                if (! in_array((int) $pid, $existing, true)) {
                    $wl->toggle((int) $user['id'], (int) $pid); // adds when absent
                }
            }
            session()->remove('wishlist');
        }
    }
}
