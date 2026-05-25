<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    protected function usersTable(): string
    {
        $user = new User();

        return "{$user->getConnectionName()}.{$user->getTable()}";
    }

    public function index()
    {
        $user = auth()->user();
        return view('profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique($this->usersTable(), 'email')->ignore($user->id)],
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        return ApiResponse::success(message: 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return ApiResponse::error('Password lama tidak sesuai.', 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return ApiResponse::success(message: 'Password berhasil diubah.');
    }
}

