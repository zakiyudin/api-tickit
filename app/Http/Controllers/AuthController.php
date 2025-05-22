<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterStoreRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            if (!Auth::guard('web')->attempt($request->only('email', 'password'))) {
                # code...
                return response([
                    'message' => 'Unauthorized',
                    'data' => null
                ], 401);
            }

            $user = Auth::user();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response([
                'message' => 'Login Berhasil',
                'data' => [
                    'token' => $token,
                    'user' => $user
                ]
            ], 200);
        } catch (Exception $e) {
            //throw $th;
            return response([
                'message' => $e->getMessage(),
                'data' => null
            ]);
        }
    }

    public function me()
    {
        try {
            $user = Auth::user();

            return response([
                'message' => 'Success',
                'data' => new UserResource($user)
            ], 201);
        } catch (Exception $e) {
            //throw $th;
            return response([
                'message' => $e->getMessage(),
                'data' => null
            ]);
        }
    }

    public function logout()
    {
        try {
            $user = Auth::user();
            $user->CurrentAccessToken()->delete();

            return response([
                'message' => 'Success',
                'data' => null,
            ], 201);
        } catch (Exception $e) {
            //throw $th;
            return response([
                'message' => $e->getMessage(),
                'data' => null
            ]);
        }
    }

    public function register(RegisterStoreRequest $registerStoreRequest)
    {
        $data = $registerStoreRequest->validated();

        DB::beginTransaction();

        try {
            //code...
            $user = new User();
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = Hash::make($data['password']);
            $user->save();

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response([
                'message' => 'Bergasi Registrasi',
                'data' => [
                    'token' => $token,
                    'user' => new UserResource($user)
                ]
            ]);
        } catch (Exception $e) {
            //throw $th;
            return response([
                'message' => $e->getMessage(),
                'data' => null
            ]);
        }
    }
}
