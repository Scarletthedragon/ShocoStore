<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Pasutijums;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

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

        $verificationSent = $this->sendVerification($customer);

        return response()->json(['user' => $this->payload($customer), 'verificationSent' => $verificationSent], 201);
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

    public function updateProfile(Request $request): JsonResponse
    {
        $customer = $request->user();
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('Lietotajs', 'Epasts')->ignore($customer->getKey(), $customer->getKeyName())],
        ]);
        $emailChanged = $customer->Epasts !== $data['email'];
        $customer->fill(['Vards' => $data['name'], 'Epasts' => $data['email']]);
        if ($emailChanged) {
            $customer->email_verified_at = null;
        }
        $customer->save();

        return response()->json([
            'user' => $this->payload($customer),
            'verificationSent' => $emailChanged ? $this->sendVerification($customer) : null,
        ]);
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password:web'],
            'confirmation' => ['required', 'accepted'],
        ], [
            'password.current_password' => 'Nepareiza parole.',
            'confirmation.accepted' => 'Apstiprini, ka vēlies neatgriezeniski dzēst kontu.',
        ]);
        $customer = $request->user();
        DB::transaction(function () use ($customer): void {
            Pasutijums::query()->where('Lietotajs_ID', $customer->getKey())->update([
                'Lietotajs_ID' => null,
                'Klienta_vards' => 'Dzēsts konts',
                'delivery_address' => null,
            ]);
            $customer->delete();
        });
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['user' => null]);
    }

    public function verifyEmail(Request $request, string $id, string $hash): RedirectResponse
    {
        $customer = Customer::query()->findOrFail($id);
        abort_unless(hash_equals(sha1($customer->getEmailForVerification()), $hash), 403);
        if (! $customer->hasVerifiedEmail() && $customer->markEmailAsVerified()) {
            event(new Verified($customer));
        }

        return redirect()->away(rtrim(config('app.frontend_url'), '/').'/#/profils');
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $customer = $request->user();
        if ($customer->hasVerifiedEmail()) {
            return response()->json(['user' => $this->payload($customer), 'alreadyVerified' => true]);
        }

        if (! $this->sendVerification($customer)) {
            return response()->json(['message' => 'Neizdevās nosūtīt apstiprinājuma e-pastu. Mēģini vēlreiz vēlāk.'], 503);
        }

        return response()->json(['user' => $this->payload($customer)]);
    }

    private function sendVerification(Customer $customer): bool
    {
        try {
            $customer->sendEmailVerificationNotification();

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    private function payload(Customer $customer): array
    {
        return [
            'id' => (string) $customer->getKey(),
            'name' => $customer->Vards,
            'email' => $customer->Epasts,
            'emailVerified' => $customer->hasVerifiedEmail(),
            'address' => $customer->delivery_address,
            'isAdmin' => $customer->hasVerifiedEmail() && config('app.admin_email') !== ''
                && hash_equals(config('app.admin_email'), mb_strtolower($customer->Epasts)),
        ];
    }
}
