<?php

namespace App\Rules;

use App\Services\TurnstileService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TurnstileRule implements ValidationRule
{
    protected $turnstile_service;

    public function __construct()
    {
        $this->turnstile_service = app(TurnstileService::class);
    }

    /**
     * バリデーション実行
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // TurnstileがOFF、もしくは設定が不足している場合は認証をスキップ
        if (! $this->turnstile_service->isConfigured()) {
            return;
        }

        // 失敗理由はあえて明記しない
        $generic_error = config('app.turnstile_error_msg', 'Bot対策認証に失敗しました。再度お試しください。');

        // 入力値の基本検証(空ではない、文字列、2048文字以内)
        if (empty($value) || ! is_string($value) || strlen($value) > 2048) {
            $fail($generic_error);

            return;
        }

        // Turnstile認証を実行
        $result = $this->turnstile_service->verify($value);

        // 認証結果が成功であればreturnして終了
        if (isset($result['success']) && $result['success'] === true) {
            return;
        }

        // 認証に失敗した場合はエラーメッセージを追加
        $fail($generic_error);
    }
}
