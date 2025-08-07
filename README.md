# Goal in One 🎯

**Goal in One**は、個人の目標管理とタスク追跡を効率化するモダンなWebアプリケーションです。美しいUI、直感的なUX、そして包括的な進捗可視化機能を提供します。

![Goal in One](https://img.shields.io/badge/Laravel-12.21.0-red?style=flat-square&logo=laravel)
![Vue.js](https://img.shields.io/badge/Vue.js-3.4.0-green?style=flat-square&logo=vue.js)
![TypeScript](https://img.shields.io/badge/TypeScript-5.3.0-blue?style=flat-square&logo=typescript)
![License](https://img.shields.io/badge/License-MIT-yellow?style=flat-square)

## ✨ 主な機能

### 🎯 目標管理
- **目標の作成・編集・削除**: 詳細な説明と目標達成日の設定
- **進捗追跡**: リアルタイムでの進捗率表示
- **ステータス管理**: アクティブ、完了、アーカイブの状態管理

### 📋 タスク管理
- **3種類のタスクタイプ**:
  - **シンプルタスク**: 一回限りのタスク
  - **繰り返しタスク**: 日次・週次・月次の定期タスク
  - **期限付きタスク**: 締切日設定可能なタスク
- **独立タスク**: 目標に紐づかない単独タスク
- **タスク完了追跡**: 完了履歴の記録

### 📊 進捗可視化
- **インタラクティブなダッシュボード**: Chart.jsによる美しいグラフ
- **統計分析**: 週間・月間・年間の進捗レポート
- **プログレスバー**: アニメーション付きの進捗表示
- **達成率分析**: 目標とタスクの完了率

### 🔐 認証システム
- **Laravel Sanctum**: セキュアなAPI認証
- **ユーザー登録・ログイン**: メール認証対応
- **プロフィール管理**: パスワード変更、アカウント削除
- **セッション管理**: 安全なログアウト機能

### 🎨 UI/UX
- **レスポンシブデザイン**: モバイル・タブレット・デスクトップ対応
- **アニメーション効果**: スムーズなページ遷移とマイクロインタラクション
- **カラフルテーマ**: TailwindCSSによる美しいデザインシステム
- **アクセシビリティ**: WCAG準拠のユーザビリティ

## 🛠 技術スタック

### バックエンド
- **Laravel 12.21.0**: PHPフレームワーク
- **Laravel Sanctum 4.2.0**: API認証
- **MariaDB 10.5.10**: データベース
- **PHP 8.2**: プログラミング言語

### フロントエンド
- **Vue.js 3.4.0**: JavaScriptフレームワーク
- **TypeScript 5.3.0**: 型安全性
- **Vite 7.1.0**: ビルドツール
- **Pinia**: 状態管理
- **TailwindCSS 3.4.17**: CSSフレームワーク
- **Chart.js + vue-chartjs**: データ可視化

### 開発・デプロイ
- **Laravel Herd**: ローカル開発環境
- **Docker**: コンテナ化
- **PHPUnit**: テスティング
- **ESLint + Prettier**: コード品質

## 🚀 インストール

### 前提条件
- PHP 8.2以上
- Node.js 18以上
- Composer
- MariaDB/MySQL

### ローカル開発環境のセットアップ

1. **リポジトリのクローン**
```bash
git clone https://github.com/your-username/goal-in-one.git
cd goal-in-one
```

2. **依存関係のインストール**
```bash
# PHP依存関係
composer install

# Node.js依存関係
npm install
```

3. **環境設定**
```bash
# 環境ファイルのコピー
cp .env.example .env

# アプリケーションキーの生成
php artisan key:generate
```

4. **データベース設定**
```bash
# .envファイルでデータベース設定を更新
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=goal_in_one
DB_USERNAME=your_username
DB_PASSWORD=your_password

# マイグレーション実行
php artisan migrate
```

5. **フロントエンド開発サーバー起動**
```bash
npm run dev
```

6. **Laravel開発サーバー起動**
```bash
php artisan serve
```

アプリケーションは `http://localhost:8000` でアクセス可能です。

## 🐳 Docker での実行

### 開発環境
```bash
# コンテナのビルドと起動
docker-compose up -d

# マイグレーション実行
docker-compose exec app php artisan migrate

# フロントエンドビルド
docker-compose exec app npm run build
```

### 本番環境
```bash
# 本番用環境設定
cp .env.production .env

# 本番環境でのビルドと起動
docker-compose -f docker-compose.prod.yml up -d
```

## 📋 デプロイメント

### 自動デプロイスクリプト
```bash
# デプロイスクリプトの実行
bash deploy.sh

# テスト付きデプロイ
bash deploy.sh --with-tests
```

### 手動デプロイ手順
1. **コードの更新**: `git pull origin main`
2. **依存関係の更新**: `composer install --no-dev --optimize-autoloader`
3. **フロントエンドビルド**: `npm ci && npm run build`
4. **設定キャッシュ**: `php artisan config:cache`
5. **マイグレーション**: `php artisan migrate --force`
6. **権限設定**: `chmod -R 755 storage bootstrap/cache`

## 🧪 テスト

### テストの実行
```bash
# 全テストの実行
php artisan test

# 特定のテストスイート
php artisan test --filter=AuthTest
php artisan test --filter=GoalTest
php artisan test --filter=TaskTest

# カバレッジレポート
php artisan test --coverage
```

### テストの種類
- **認証テスト**: ユーザー登録、ログイン、ログアウト
- **目標管理テスト**: CRUD操作、権限チェック
- **タスク管理テスト**: 3種類のタスクタイプ、完了処理
- **API統合テスト**: エンドポイントの動作確認

## 📊 API ドキュメント

### 認証エンドポイント
```
POST /api/auth/register    # ユーザー登録
POST /api/auth/login       # ログイン
POST /api/auth/logout      # ログアウト
GET  /api/auth/user        # ユーザー情報取得
DELETE /api/auth/account   # アカウント削除
```

### 目標管理エンドポイント
```
GET    /api/goals          # 目標一覧取得
POST   /api/goals          # 目標作成
GET    /api/goals/{uuid}   # 目標詳細取得
PUT    /api/goals/{uuid}   # 目標更新
DELETE /api/goals/{uuid}   # 目標削除
```

### タスク管理エンドポイント
```
GET    /api/tasks                    # 全タスク取得
POST   /api/tasks                    # 独立タスク作成
GET    /api/goals/{uuid}/tasks       # 目標別タスク取得
POST   /api/goals/{uuid}/tasks       # 目標タスク作成
PUT    /api/tasks/{uuid}             # タスク更新
DELETE /api/tasks/{uuid}             # タスク削除
POST   /api/tasks/{uuid}/complete    # タスク完了
```

### ダッシュボードエンドポイント
```
GET /api/dashboard/stats                    # 統計データ取得
GET /api/dashboard/goals/{uuid}/progress    # 目標進捗取得
```

## 🔧 設定

### 環境変数
主要な環境変数の説明：

```env
# アプリケーション設定
APP_NAME="Goal in One"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# データベース設定
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=goal_in_one_production
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password

# Sanctum設定
SANCTUM_STATEFUL_DOMAINS=your-domain.com
SESSION_DOMAIN=.your-domain.com
```

### セキュリティ設定
- HTTPS強制リダイレクト
- HSTS ヘッダー設定
- XSS保護
- CSRF保護
- Content-Type Sniffing防止

## 🤝 コントリビューション

1. **フォーク**: このリポジトリをフォーク
2. **ブランチ作成**: `git checkout -b feature/amazing-feature`
3. **コミット**: `git commit -m 'Add amazing feature'`
4. **プッシュ**: `git push origin feature/amazing-feature`
5. **プルリクエスト**: Pull Requestを作成

### 開発ガイドライン
- **コードスタイル**: PSR-12準拠
- **コミットメッセージ**: Conventional Commits形式
- **テスト**: 新機能には必ずテストを追加
- **ドキュメント**: APIの変更時はドキュメント更新

## 📝 ライセンス

このプロジェクトは[MIT License](LICENSE)の下で公開されています。

## 🙏 謝辞

- **Laravel**: 素晴らしいPHPフレームワーク
- **Vue.js**: リアクティブなフロントエンドフレームワーク
- **TailwindCSS**: ユーティリティファーストCSSフレームワーク
- **Chart.js**: 美しいデータ可視化ライブラリ

## 📞 サポート

- **Issues**: [GitHub Issues](https://github.com/your-username/goal-in-one/issues)
- **Discussions**: [GitHub Discussions](https://github.com/your-username/goal-in-one/discussions)
- **Email**: support@goal-in-one.com

---

**Goal in One**で、あなたの目標達成の旅を始めましょう！ 🚀✨
