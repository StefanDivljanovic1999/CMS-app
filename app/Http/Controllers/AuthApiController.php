<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthApiController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();

        if ($user->profile_picture) {
            $user->profile_picture = url($user->profile_picture);
        }

        return response()->json([
            'status' => 'success',
            'data' => $user
        ], 200);
    }
    public function register(Request $request)
    {
        //prvo cemo proveriti unos
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2',
            'email' => 'required|email',
            'password' => 'required|min:5',
            'role' => ['nullable', Rule::in(['admin', 'author', 'reader'])],
            'profile_picture' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()
            ], 400);
        }

        //ako postoji user sa datim email-om prekini izvrsavanje
        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'status' => 'fail',
                'message' => 'User with email: ' . $request->email . ' alredy exist in database!'
            ], 400);
        }

        //za slucaj kada korisnik uz podatke prilaze i profilnu sliku
        $imagePath = null;
        if ($request->hasFile('profile_picture') && $request->file('profile_picture')) {
            $file = $request->file('profile_picture');
            //generisacemo ime fajla tako da bude preglednije u storage-u
            $fileName = time() . '_' . $file->getClientOriginalName();
            //premestamo fajl u storage
            $file->move(public_path('storage/profile_pictures'), $fileName);
            //cuvamo fajl u bazi
            $imagePath = "storage/profile_pictures/" . $fileName;
        }

        //kreiramo novog korisnika, tj. registrujemo
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role,
            'profile_picture' => $imagePath
        ]);



        return response()->json([
            'status' => 'success',
            'message' => 'User successfully registered'
        ], 201);
    }

    public function login(Request $request)
    {
        //prvo cemo proveriti unos
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:5'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        //ako mail ne postoji u bazi, prekidamo izvrsavanje
        if (!$user->exists()) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Email: ' . $request->email . ' doesnt exist in the base!!!'
            ], 404);
        } else {
            //proveravamo da li se lozinke podudaraju
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Invalid credentials!!!'
                ], 403);
            } else {
                $response['token'] = $user->createToken('CMS-app')->plainTextToken;
                $response['name'] = $user->name;
                $response['email'] = $user->email;
                $response['role'] = $user->role;
                $response['user_id'] = $user->id;
                return response()->json([
                    'status' => 'success',
                    'message' => 'User successfully logged in!',
                    'data' => $response
                ], 200);
            }
        }
    }

    public function logout(Request $request)
    {
        //proveravamo da li zadati user postoji
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'User successfulyy logged out!'
        ]);
    }
}
