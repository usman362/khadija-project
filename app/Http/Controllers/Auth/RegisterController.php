<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Contracts\UserRegistrationServiceInterface;
use App\Domain\Auth\DataTransferObjects\RegisterUserData;
use App\Http\Controllers\Controller;
use App\Rules\Recaptcha;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/dashboard';

    public function __construct(private readonly UserRegistrationServiceInterface $userRegistrationService)
    {
        $this->middleware('guest');
    }

    /**
     * Show the registration form.
     */
    public function showRegistrationForm(Request $request)
    {
        $role = $request->query('role', 'client');

        return view('auth.register', compact('role'));
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', 'string', 'in:client,supplier'],
            'g-recaptcha-response' => [new Recaptcha('register')],
        ]);
    }

    protected function create(array $data)
    {
        $role = $data['role'] ?? 'client';

        return $this->userRegistrationService->register(
            new RegisterUserData(
                name: (string) $data['name'],
                email: (string) $data['email'],
                password: (string) $data['password'],
                role: (string) $role,
            )
        );
    }
}
