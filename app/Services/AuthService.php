<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private CloudinaryService $cloudinary) {}

    public function registerUser(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'phone'    => $data['phone'] ?? null,
            'role'     => 'user',
        ]);

        $token = auth('api')->login($user);

        return ['user' => $user, 'token' => $token];
    }

    public function registerPartner(array $data, mixed $photo = null): array
    {
        return DB::transaction(function () use ($data, $photo) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => $data['password'],
                'phone'    => $data['phone'] ?? null,
                'role'     => 'partner',
            ]);

            $photoUrl = $photo ? $this->cloudinary->upload($photo, 'shops') : null;

            Shop::create([
                'user_id'          => $user->id,
                'shop_name'        => $data['shop_name'],
                'shop_address'     => $data['shop_address'],
                'shop_phone'       => $data['shop_phone'] ?? null,
                'shop_description' => $data['shop_description'] ?? null,
                'shop_photo_url'   => $photoUrl,
                'open_time'        => $data['open_time'],
                'close_time'       => $data['close_time'],
                'operating_days'   => $data['operating_days'],
                'latitude'         => $data['latitude'] ?? null,
                'longitude'        => $data['longitude'] ?? null,
                'status'           => 'pending',
            ]);

            $token = auth('api')->login($user);

            return ['user' => $user->load('shop'), 'token' => $token];
        });
    }

    public function login(array $credentials): array
    {
        $token = auth('api')->attempt([
            'email'    => $credentials['email'],
            'password' => $credentials['password'],
        ]);

        if (!$token) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $user = auth('api')->user();

        if ($user->role === 'partner') {
            $user->load('shop');
        }

        return ['user' => $user, 'token' => $token];
    }

    public function logout(): void
    {
        auth('api')->logout();
    }

    public function me(): User
    {
        $user = auth('api')->user();

        if ($user->role === 'partner') {
            $user->load('shop');
        }

        return $user;
    }

    public function updateAvatar(mixed $file): User
    {
        $user = auth('api')->user();
        $url  = $this->cloudinary->upload($file, 'avatars');
        $user->update(['avatar_url' => $url]);

        return $user;
    }

    public function updateProfile(array $data): User
    {
        $user = auth('api')->user();
        $user->update($data);

        return $user->fresh();
    }
}
