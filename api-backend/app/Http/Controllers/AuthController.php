<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthController extends Controller
{
    // REGISTRO DE USUARIO
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            // Quitamos Hash::make() porque Laravel 11 ya lo hace en automático
            'password' => $request->password,
        ]);

        return response()->json([
            'mensaje' => 'Usuario registrado con exito',
            'user' => $user
        ], 201);
    }

    // LOGIN DE USUARIO
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Buscamos al usuario en la base de datos
        $user = User::where('email', $request->email)->first();

        // Verificamos que exista y que la contraseña coincida
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['mensaje' => 'Credenciales incorrectas'], 401);
        }

        // Generamos un "pase de entrada" (Token)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'mensaje' => 'Bienvenido',
            'token' => $token,
            'user' => $user
        ], 200);
    }

    // --- NUEVO: INICIO DE SESIÓN CON GOOGLE ---
    public function googleSignIn(Request $request)
    {
        // 1. Validamos que Flutter nos envíe el correo y el nombre
        $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
        ]);

        // 2. Buscamos si el usuario ya existe en la base de datos
        $user = \App\Models\User::where('email', $request->email)->first();

        // 3. Si no existe, lo creamos automáticamente
        if (!$user) {
            $user = \App\Models\User::create([
                'name' => $request->name,
                'email' => $request->email,
                // Le asignamos una contraseña aleatoria y encriptada porque entrará con Google
                'password' => bcrypt(\Illuminate\Support\Str::random(16)), 
            ]);
        }

        // 4. Generamos el Token de seguridad de Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. Devolvemos el Token a Flutter
        return response()->json([
            'mensaje' => 'Bienvenido',
            'token' => $token,
            'user' => $user
        ], 200);
    }

    // --- NUEVO: RECUPERACIÓN DE CONTRASEÑA ---
    public function forgotPassword(Request $request)
    {
        // 1. Validamos que nos envíen un correo válido
        $request->validate([
            'email' => 'required|email'
        ]);

        // 2. Buscamos al usuario en la base de datos
        $user = \App\Models\User::where('email', $request->email)->first();

        // Por seguridad, si el correo no existe devolvemos un 200 igual para evitar 
        // que los hackers adivinen qué correos están registrados.
        if (!$user) {
            return response()->json(['mensaje' => 'Si el correo existe, recibirás un mensaje.'], 200);
        }

        // 3. Generamos un PIN numérico aleatorio de 6 dígitos
        $pin = rand(100000, 999999);

        // 4. Guardamos el PIN en la tabla de reseteo de Laravel
        // (En Laravel 10+ la tabla se llama password_reset_tokens, en versiones anteriores password_resets)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => $pin,
                'created_at' => Carbon::now()
            ]
        );

        // 5. Enviamos el correo físico usando Mail::raw (sin necesidad de crear vistas HTML complejas)
        $mensaje = "Hola {$user->name},\n\n"
                 . "Has solicitado restablecer tu contraseña en la app Acceso Ciudadano.\n\n"
                 . "Tu código de seguridad es: {$pin}\n\n"
                 . "Ingresa este código en la aplicación para crear tu nueva contraseña. "
                 . "Si no solicitaste este cambio, ignora este mensaje.";

        Mail::raw($mensaje, function ($mail) use ($user) {
            $mail->to($user->email)
                 ->subject('Código de Recuperación - Acceso Ciudadano');
        });

        return response()->json(['mensaje' => 'Correo enviado correctamente'], 200);
    }

    // --- NUEVO: RESTABLECER CONTRASEÑA ---
    public function resetPassword(Request $request)
    {
        // 1. Validamos los datos que envía Flutter
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|numeric', // Este es el PIN
            'password' => 'required|min:6'
        ]);

        // 2. Buscamos el PIN en la tabla de reseteo
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        // 3. Si no existe o el PIN es incorrecto
        if (!$resetRecord) {
            return response()->json(['mensaje' => 'El código PIN es incorrecto o ha expirado'], 400);
        }

        // 4. Si el PIN es correcto, actualizamos la contraseña del usuario
        $user = \App\Models\User::where('email', $request->email)->first();
        if ($user) {
            $user->password = bcrypt($request->password);
            $user->save();
        }

        // 5. Borramos el PIN de la tabla por seguridad (ya se usó)
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['mensaje' => 'Contraseña actualizada correctamente'], 200);
    }
}