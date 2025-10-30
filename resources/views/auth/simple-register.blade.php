<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>新規登録 - Goal in One</title>
    <link rel="stylesheet" href="{{ asset('css/simple-register.css') }}">
    @if(is_turnstile_configured())
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
</head>
<body>
    <div class="container">
        <h1>アカウント作成</h1>
        <p class="subtitle">Goal in Oneを始めましょう</p>

        @if(session('success'))
            <div class="success-message">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('simple-register.store') }}">
            @csrf

            <div class="form-group">
                <label for="name">お名前</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    class="@error('name') error-input @enderror"
                    required
                    autofocus
                    autocomplete="name"
                >
                @error('name')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="@error('email') error-input @enderror"
                    required
                    autocomplete="email"
                >
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="@error('password') error-input @enderror"
                    required
                    autocomplete="new-password"
                >
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">パスワード確認</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                >
            </div>

            {{-- Honeypot: 人間には見えない、ボットだけが入力するフィールド --}}
            <input
                type="text"
                name="{{ config('app.honeypot_field_name') }}"
                style="position:absolute;left:-9999px"
                tabindex="-1"
                autocomplete="off"
                aria-hidden="true"
            >

            @if(is_turnstile_configured())
                <div class="form-group">
                    <div class="cf-turnstile" data-sitekey="{{ config('app.turnstile_site_key') }}" data-language="ja"></div>
                    @error('cf-turnstile-response')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
            @endif

            <button type="submit" class="btn">アカウントを作成</button>
        </form>

        <div class="login-link">
            すでにアカウントをお持ちですか？ <a href="/">ログイン</a>
        </div>
    </div>
</body>
</html>
