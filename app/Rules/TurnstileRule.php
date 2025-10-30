<?php

namespace App\Rules;

use App\Services\TurnstileService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Turnstile検証ルール
 *
 * Cloudflare TurnstileのトークンをバリデーションするLaravel ValidationRule
 */
class TurnstileRule implements ValidationRule
{
    protected $turnstile_service;

    public function __construct()
    {
        $this->turnstile_service = app(TurnstileService::class);
    }

    /**
     * バリデーション実行
     *
     * @param string $attribute バリデーション対象の属性名（'cf-turnstile-response'）
     * @param mixed $value フロントエンドから送信されたTurnstileトークン
     * @param Closure $fail バリデーション失敗時に呼び出すクロージャ
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Turnstile機能が無効、または設定不足の場合は認証をスキップ
        if (! $this->turnstile_service->isConfigured()) {
            return;  // バリデーション通過（開発環境などで便利）
        }

        // セキュリティ上、詳細を明かさない汎用的なエラーメッセージ
        $generic_error = config('app.turnstile_error_msg', 'Bot対策認証に失敗しました。再度お試しください。');

        // 入力値の基本検証(空ではない、文字列、2048文字以内)
        if (empty($value) || ! is_string($value) || strlen($value) > 2048) {
            $fail($generic_error);
            return;
        }

        // CloudflareのAPIにトークンを送信して検証
        $result = $this->turnstile_service->verify($value);

        // 認証成功の場合は処理を終了
        if (isset($result['success']) && $result['success'] === true) {
            return;
        }

        // 認証失敗の場合はエラーを追加
        $fail($generic_error);
    }
}
