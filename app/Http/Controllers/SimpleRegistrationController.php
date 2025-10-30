<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SimpleRegistrationController extends Controller
{
    /**
     * 登録フォームを表示
     */
    public function showRegistrationForm()
    {
        return view('auth.simple-register');
    }

    /**
     * ユーザー登録処理
     */
    public function register(StoreUserRequest $request)
    {
        // Form Requestによる自動バリデーション完了後の処理
        $validated = $request->validated();

        // ユーザー作成
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // 登録完了後、ログインページにリダイレクト
        return redirect('/login')->with('success', '登録が完了しました！ログインしてください。');
    }
}
