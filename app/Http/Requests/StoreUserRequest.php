<?php

namespace App\Http\Requests;

use App\Rules\TurnstileRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    /**
     * リクエストの認可判定
     */
    public function authorize(): bool
    {
        // ゲストユーザーによる登録を許可
        return true;
    }

    /**
     * バリデーションルールを取得
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $honeypot_field = config('app.honeypot_field_name', 'company_name');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            $honeypot_field => ['nullable', 'max:0'], // Honeypot: 空であるべき
        ];
    }

    /**
     * バリデーション後の追加処理
     * Turnstile認証をここで実行することで、基本的なバリデーションと分離
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Turnstile認証を実行
            $turnstile_rule = new TurnstileRule;

            $turnstile_rule->validate(
                'cf-turnstile-response',
                $this->input('cf-turnstile-response'),
                function (string $message) use ($validator) {
                    $validator->errors()->add('cf-turnstile-response', $message);
                }
            );
        });
    }

    /**
     * カスタムエラーメッセージを取得
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $honeypot_field = config('app.honeypot_field_name', 'company_name');

        return [
            'name.required' => 'お名前は必須項目です。',
            'email.required' => 'メールアドレスは必須項目です。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'email.unique' => 'このメールアドレスは既に登録されています。',
            'password.required' => 'パスワードは必須項目です。',
            'password.confirmed' => 'パスワード確認が一致しません。',
            "{$honeypot_field}.max" => '入力内容に問題があります。再度お試しください。',
        ];
    }

    /**
     * バリデーション属性名をカスタマイズ
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'お名前',
            'email' => 'メールアドレス',
            'password' => 'パスワード',
        ];
    }
}
