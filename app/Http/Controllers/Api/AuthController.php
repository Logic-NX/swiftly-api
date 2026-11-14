<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validamos que envíen email y contraseña
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Buscamos al usuario
        $user = User::where('email', $request->email)->first();

        // 3. Verificamos si existe y si la clave es correcta
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // 4. Generamos el Token de acceso (La "llave" para la PWA)
        $token = $user->createToken('swiftly_pos')->plainTextToken;

        // 5. Devolvemos el token y los datos del cajero
        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'company_id' => $user->company_id
            ]
        ]);
    }

    public function logout(Request $request)
    {
        // Destruimos la "llave" actual
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => 'Sesión cerrada con éxito']);
    }
}