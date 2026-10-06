<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Users\UserCreationService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class RegisterController extends Controller
{
    public function __construct(
        private UserCreationService $creationService,
        private ConfigRepository $config,
    ) {
    }

    /**
     * Handle a registration request to the application.
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function register(Request $request): JsonResponse
    {
        // Check if registration is enabled via .env
        if (!$this->config->get('auth.allow_registration', false)) {
            return response()->json(['errors' => [['detail' => 'Registration is currently disabled.']]], 403);
        }

        $request->validate([
            'email'      => 'required|email|max:191|unique:users,email',
            'username'   => 'required|string|min:3|max:191|unique:users,username',
            'name_first' => 'required|string|max:191',
            'name_last'  => 'required|string|max:191',
            'password'   => 'required|string|min:8',
        ]);

        $this->creationService->handle([
            'email'      => $request->input('email'),
            'username'   => $request->input('username'),
            'name_first' => $request->input('name_first'),
            'name_last'  => $request->input('name_last'),
            'password'   => $request->input('password'),
            'root_admin' => false,
        ]);

        return response()->json(['success' => true]);
    }
}
