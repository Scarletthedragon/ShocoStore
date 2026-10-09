<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function csrf(Request $request): JsonResponse
    {
        return response()->json(['token' => $request->session()->token()]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email'))), 'name' => trim((string) $request->input('name'))]);
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', 'max:255', 'unique:Lietotajs,Epasts'], 'password' => ['required', 'string', 'confirmed', Password::min(8)]]);
        $customer = Customer::query()->create(['Vards' => $data['name'], 'Epasts' => $data['email'], 'Parole' => $data['password']]);
        Auth::login($customer);
        $request->session()->regenerate();

        return response()->json(['user' => $this->payload($customer)], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt(['Epasts' => mb_strtolower(trim($data['email'])), 'password' => $data['password']])) {
            throw ValidationException::withMessages(['email' => 'Nepareizs e-pasts vai parole.']);
        }
        $request->session()->regenerate();

        return response()->json(['user' => $this->payload($request->user())]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->payload($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['user' => null]);
    }

    private function payload(Customer $customer): array
    {
        return ['id' => (string) $customer->getKey(), 'name' => $customer->Vards, 'email' => $customer->Epasts];
    }
}
