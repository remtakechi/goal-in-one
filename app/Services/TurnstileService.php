<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Turnstile（Cloudflareのボット対策ツール）の管理サービス
 *
 * @see https://developers.cloudflare.com/turnstile/
 */
class TurnstileService
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct()
    {
        $this->client = $this->createHttpClient();
    }

    /**
     * HTTPクライアントを適切な設定で作成
     */
    private function createHttpClient(): Client
    {
        return new Client([
            'timeout' => 10, // リクエスト全体のタイムアウト（秒）
            'connect_timeout' => 5, // 接続確立のタイムアウト（秒）
        ]);
    }

    /**
     * Turnstileがグローバルに有効化され、正しく設定されているかを確認
     */
    public function isConfigured(): bool
    {
        return config('app.is_use_turnstile', false) &&
               ! empty(config('app.turnstile_site_key', '')) &&
               ! empty(config('app.turnstile_secret_key', ''));
    }

    /**
     * TurnstileトークンをCloudflare APIで検証
     *
     * @param  string  $token  cf-turnstile-responseの値
     * @return array ['success' => bool, 'error-codes'? => array]
     */
    public function verify(string $token): array
    {
        $turnstile_secret_key = config('app.turnstile_secret_key', '');
        $turnstile_verify_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

        try {
            // Cloudflareの認証APIにリクエストを送信
            $response = $this->client->post($turnstile_verify_url, [
                'json' => [
                    'secret' => $turnstile_secret_key,
                    'response' => $token,
                ],
            ]);

            // レスポンスボディを取得してJSONデコード
            $body = $response->getBody()->getContents();
            $result = json_decode($body, true);

            // レスポンスが空の場合は失敗として扱う
            if (empty($result)) {
                return ['success' => false];
            }

            return $result;
        } catch (GuzzleException $e) {
            // エラーログを記録
            Log::error('Turnstile認証に失敗しました: '.$e->getMessage(), [
                'exception_class' => get_class($e),
                'token_length' => strlen($token),
                'is_configured' => $this->isConfigured(),
            ]);

            return ['success' => false];
        }
    }
}
