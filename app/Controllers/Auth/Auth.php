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

        // F-007: Throttling against brute-force login attempts
        $throttler   = service('throttler');
        $ip          = $this->request->getIPAddress();
        $email       = strtolower(trim((string) $this->request->getPost('email')));
        $throttleKey = 'login_' . md5($ip . '_' . $email);
        if (! $throttler->check($throttleKey, 5, 60)) {
            return redirect()->back()->withInput()->with('error', 'Too many failed login attempts. Please wait 60 seconds before trying again.');
        }

        $users = new UserModel();
        $user  = $users->findByEmail($email);

        if (! $user || ! $users->verifyPassword($this->request->getPost('password'), $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'The email or password is incorrect.');
        }
        if ($user['status'] !== 'active') {
            return redirect()->back()->with('error', 'This account has been suspended.');
        }

        // F-007: Regenerate session ID to prevent session fixation attacks
        session()->regenerate();

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
        $email = strtolower(trim((string) $this->request->getPost('email')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Please enter a valid email address.');
        }

        $throttler   = service('throttler');
        $throttleKey = 'pwreset_' . md5($this->request->getIPAddress());
        if (! $throttler->check($throttleKey, 5, 300)) {
            return redirect()->back()->with('error', 'Too many password reset requests. Please wait a few minutes.');
        }

        $users = new UserModel();
        $user  = $users->findByEmail($email);
        if ($user && $user['status'] === 'active') {
            $resetModel = new \App\Models\PasswordResetModel();
            $token      = $resetModel->createToken($email);
            $resetUrl   = site_url('reset-password/' . $token);

            try {
                $emailService = \Config\Services::email();
                $emailService->setTo($email);
                $emailService->setSubject('Password Reset — ' . store_name());
                $emailService->setMessage("Hello " . esc($user['first_name']) . ",\n\nWe received a request to reset your password. Click the link below to set a new password:\n\n{$resetUrl}\n\nThis link is valid for 1 hour. If you did not request this, you can safely ignore this email.\n\nWarm regards,\n" . store_name());
                @$emailService->send(false);
            } catch (\Throwable $e) {
                log_message('error', 'Password reset email failed: ' . $e->getMessage());
            }

            log_message('info', "Password reset link generated for {$email}: {$resetUrl}");
        }

        return redirect()->to(site_url('forgot-password'))
            ->with('success', 'If that email is registered, we have sent reset instructions.');
    }

    public function reset(string $token)
    {
        $resetModel = new \App\Models\PasswordResetModel();
        $record     = $resetModel->verifyToken($token);
        if (! $record) {
            return redirect()->to(site_url('forgot-password'))
                ->with('error', 'This password reset link is invalid or has expired.');
        }

        return view('auth/reset', [
            'meta'  => ['title' => 'Set New Password — ' . store_name()],
            'token' => $token,
        ]);
    }

    public function attemptReset(string $token)
    {
        $resetModel = new \App\Models\PasswordResetModel();
        $record     = $resetModel->verifyToken($token);
        if (! $record) {
            return redirect()->to(site_url('forgot-password'))
                ->with('error', 'This password reset link is invalid or has expired.');
        }

        $rules = [
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];
        $messages = [
            'password'         => ['min_length' => 'Password must be at least 8 characters.'],
            'password_confirm' => ['matches' => 'Passwords do not match.'],
        ];
        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $users = new UserModel();
        $user  = $users->findByEmail($record['email']);
        if (! $user) {
            return redirect()->to(site_url('forgot-password'))
                ->with('error', 'User account not found.');
        }

        $users->update($user['id'], [
            'password_hash' => $users->hashPassword((string) $this->request->getPost('password')),
        ]);
        $resetModel->deleteToken($token);

        return redirect()->to(site_url('login'))
            ->with('success', 'Your password has been reset successfully. Please sign in with your new password.');
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
