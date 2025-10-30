# Turnstileボット対策機能

## 概要

Turnstileは、Cloudflareが提供する無料のボット対策ツールです。
reCAPTCHAの代替として、ユーザーフレンドリーな方法でボットによる不正登録を防止できます。

Turnstileの検証機能を簡単に実装できるサービスとバリデーションルールを提供しています。

### 主な特徴

- グローバル設定でTurnstileのON/OFFの切り替えができる

## 設定方法

### 1. Cloudflare Turnstileの設定

1. [Cloudflare Dashboard](https://dash.cloudflare.com/)にログイン
2. メニューのTurnstileセクションで**Widget**(新しいウィジェット)を追加
3. **Site Key**（サイトキー）と**Secret Key**（秘密キー）を発行

### 2. 環境変数の設定

`.env`ファイルに以下を追加：

```env
# Turnstileを使用するかどうか
IS_USE_TURNSTILE=true

# CloudflareのTurnstile管理画面で発行されたサイトキー（公開キー）
TURNSTILE_SITE_KEY=

# CloudflareのTurnstile管理画面で発行された秘密キー
TURNSTILE_SECRET_KEY=
```

### 3. 設定ファイル

`config/app.php`に以下の設定が自動的に読み込まれます：

```php
// Turnstileを使用するかどうか
'is_use_turnstile' => (bool) env('IS_USE_TURNSTILE', false),

// Turnstileのサイト鍵
'turnstile_site_key' => env('TURNSTILE_SITE_KEY', ''),

// Turnstileの秘密鍵
'turnstile_secret_key' => env('TURNSTILE_SECRET_KEY', ''),

// Turnstile認証失敗時のエラーメッセージ
'turnstile_error_msg' => 'Bot認証に失敗しました。',
```

## 実装方法

### 1. Form Requestにバリデーションを追加

既存のForm Requestに以下のメソッドを追加：

```php
use App\Rules\TurnstileRule;
use Illuminate\Validation\Validator;

class YourFormRequest extends FormRequest
{
    // 既存のコード...

    /**
     * Turnstile認証を追加
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
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
}
```

### 2. Bladeテンプレートに追加

#### `<head>`内に追加：

```blade
@if(is_turnstile_configured())
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
```

#### `<form>`内に追加：

```blade
@if(is_turnstile_configured())
    <div class="form-group">
        <div class="cf-turnstile" data-sitekey="{{ config('app.turnstile_site_key') }}" data-language="ja"></div>
        @error('cf-turnstile-response')
            <div class="error">{{ $message }}</div>
        @enderror
    </div>
@endif
```

### 完了！

これだけでTurnstileによるボット対策が有効になります。
設定が無効な場合は自動的にスキップされます。

## 提供されるクラスとメソッド

### TurnstileService

`app/Services/TurnstileService.php`

Turnstileの設定確認とAPIでの認証を行うサービスクラス

**動作：**

- `isConfigured(): bool` - Turnstileが有効かつ正しく設定されているかを確認
- `verify(string $token): array` - Cloudflare APIでトークンを検証

### TurnstileRule

`app/Rules/TurnstileRule.php`

ValidationRuleインターフェースを実装したカスタムバリデーションルール（Form Request内で使用）

**動作：**
- Turnstileが無効な場合、バリデーションをスキップ
- 有効な場合のみCloudflare APIで検証
- `cf-turnstile-response`はTurnstile公式JSがbladeに自動追記するトークン名

### ヘルパー関数

`app/helpers.php`（composer.jsonに追記して、自動読み込み可能にする）

#### `is_turnstile_configured(): bool`

Turnstileが有効かつ正しく設定されているかを判定するヘルパー関数

**使用方法：**

```blade
{{-- Bladeテンプレートで使用 --}}
@if(is_turnstile_configured())
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif

@if(is_turnstile_configured())
    <div class="cf-turnstile" data-sitekey="{{ config('app.turnstile_site_key') }}"></div>
@endif
```

**内部実装：**

```php
function is_turnstile_configured()
{
    return app(TurnstileService::class)->isConfigured();
}
```

## トラブルシューティング

### Turnstileウィジェットが表示されない

1. サイトキーが正しく設定されているか確認
2. `.env`の`IS_USE_TURNSTILE`が`true`になっているか確認

### バリデーションエラーが出ない

1. 秘密キーが正しく設定されているか確認
2. Form Requestで`TurnstileRule`が正しく実装されているか確認
3. コントローラーでForm Requestを使用しているか確認（型ヒント）

### テスト環境での動作確認

Cloudflareが提供するテスト用サイトキー：

```env
# 常に成功するテスト用キー
TURNSTILE_SITE_KEY=1x00000000000000000000AA

# 常に失敗するテスト用キー
TURNSTILE_SITE_KEY=2x00000000000000000000AB

# チャレンジが表示されるテスト用キー
TURNSTILE_SITE_KEY=3x00000000000000000000FF
```

## セキュリティに関する注意

1. **秘密キーは公開しないでください**
   - Bladeテンプレートには秘密キーを出力しない

2. **サイトキーは公開されても問題ありません**
   - HTMLソースに含まれます
   - Cloudflare側でドメイン検証が行われます

3. **必ずサーバーサイドで検証してください**
   - フロントエンドのみの検証は簡単にバイパスされます
   - `TurnstileRule`を使用して必ずバックエンドで検証

## 参考リンク

- [Cloudflare Turnstile公式ドキュメント](https://developers.cloudflare.com/turnstile/)
- [Turnstile管理画面](https://dash.cloudflare.com/)
