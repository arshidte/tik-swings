<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Helpers loaded for every controller / view in the app.
     *
     * @var list<string>
     */
    protected $helpers = ['url', 'form', 'text', 'format', 'store'];

    protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        $this->session = service('session');
    }

    /**
     * Read a request value from a JSON body, POST, or GET (in that order).
     * Lets AJAX endpoints accept application/json payloads (§63).
     */
    protected function input(string $key, $default = null)
    {
        $json = $this->request->getJSON(true);
        if (is_array($json) && array_key_exists($key, $json)) {
            return $json[$key];
        }
        $value = $this->request->getPost($key);

        return $value ?? $this->request->getGet($key) ?? $default;
    }

    /**
     * Standard JSON envelope for AJAX responses (§43).
     */
    protected function jsonSuccess(string $message = '', array $data = [], int $status = 200): ResponseInterface
    {
        $data['csrf'] = csrf_hash(); // keep the client token fresh (§63)

        return $this->response->setStatusCode($status)->setJSON([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ]);
    }

    protected function jsonError(string $message, int $status = 400, array $data = []): ResponseInterface
    {
        $data['csrf'] = csrf_hash();

        return $this->response->setStatusCode($status)->setJSON([
            'success' => false,
            'message' => $message,
            'data'    => $data,
        ]);
    }
}
