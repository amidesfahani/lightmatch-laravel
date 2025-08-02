<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $users = User::with(['wallets'])
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(function ($user) {
                $wallets = $user->wallets->map(function ($wallet) {
                    return [
                        'symbol' => $wallet->symbol,
                        'balance' => $wallet->balance,
                        'frozen_balance' => $wallet->frozen_balance,
                    ];
                })->toArray();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'wallets' => $wallets,
                ];
            });

        return response()->json([
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'total_pages' => $users->lastPage(),
                'total' => $users->total(),
                'per_page' => $users->perPage(),
            ],
        ], 200);
    }
}
