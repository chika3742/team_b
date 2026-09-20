---
title: ディレクトリ構成
nav_order: 3
---

# {{ page.title }}

<details open markdown="block">
  <summary>
    目次
  </summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

## 全体像

```
app/             バックエンドの PHP コード
routes/          ルート定義
database/        マイグレーション・ファクトリ・シーダー
config/          設定ファイル
bootstrap/       アプリの起動とミドルウェア登録
tests/           Pest のテスト
resources/js/    React（Inertia のページとコンポーネント）
resources/css/   Tailwind
resources/views/ ルートの Blade テンプレート1枚だけ
public/          ドキュメントルート。ビルド成果物の出力先
storage/         ログ・キャッシュ・アップロードファイル
docs/            このドキュメント群
```

リクエストは `public/index.php` → `bootstrap/app.php` → `routes/web.php` → コントローラ →
`Inertia::render()` → `resources/js/pages/*.tsx` と流れます。
Blade は `resources/views/app.blade.php` の1枚だけで、画面はすべて React です。

## バックエンド

### app/

| パス                | 役割                                                                                                          |
| ------------------- | ------------------------------------------------------------------------------------------------------------- |
| `Actions/Fortify/`  | Fortify（認証パッケージ）から呼ばれるアクション。ユーザー登録とパスワードリセットの実処理                     |
| `Concerns/`         | 複数箇所で使うバリデーションルールの trait                                                                    |
| `Console/Commands/` | 自作 Artisan コマンドの置き場所。現在は空                                                                     |
| `Http/Controllers/` | コントローラ。`Settings/` にアカウント設定まわり                                                              |
| `Http/Middleware/`  | `HandleInertiaRequests` が全ページ共有の props を定義。`HandleAppearance` がライト/ダークの設定を View に渡す |
| `Http/Requests/`    | フォームリクエスト（バリデーション）                                                                          |
| `Models/`           | Eloquent モデル。現在は `User` のみ                                                                           |
| `Providers/`        | サービスプロバイダ。`FortifyServiceProvider` で認証の挙動を設定                                               |

モデルやコントローラを追加するときは `php artisan make:` を使ってください。

### routes/

| ファイル       | 役割                                                       |
| -------------- | ---------------------------------------------------------- |
| `web.php`      | Web のルート。末尾で `settings.php` を読み込む             |
| `settings.php` | アカウント設定（プロフィール・セキュリティ・外観）のルート |
| `console.php`  | クロージャで定義する Artisan コマンド                      |

ログイン・登録・パスワードリセットのルートはここにはありません。Fortify がパッケージ側で登録します。

### database/

| パス              | 役割                                                                        |
| ----------------- | --------------------------------------------------------------------------- |
| `migrations/`     | スキーマ定義                                                                |
| `factories/`      | テスト用のダミーデータ生成                                                  |
| `seeders/`        | 初期データ投入                                                              |
| `database.sqlite` | スターターキットが作るファイル。この構成では MySQL を使うので参照されません |

### config/

Laravel の設定ファイルです。`.env` で変えられるものは `.env` 側で変えてください。
このプロジェクトで中身を見る機会があるのは次の2つです。

- `fortify.php` — 有効にする認証機能（登録・二段階認証・メール確認など）の切り替え
- `inertia.php` — SSR の有無と、ページコンポーネントを探すパス

### bootstrap/

| パス            | 役割                                                                                              |
| --------------- | ------------------------------------------------------------------------------------------------- |
| `app.php`       | ミドルウェアの登録、例外ハンドリング、ルートファイルの指定。Laravel 10 までの `Kernel.php` に相当 |
| `providers.php` | 読み込むサービスプロバイダの一覧                                                                  |
| `cache/`        | フレームワークが生成するキャッシュ                                                                |
| `ssr/`          | SSR ビルドの出力先                                                                                |

### tests/

| パス           | 役割                                                         |
| -------------- | ------------------------------------------------------------ |
| `Feature/`     | HTTP リクエストを通すテスト。大半はここに書きます            |
| `Unit/`        | 単体テスト                                                   |
| `Pest.php`     | `Feature/` 全体に `RefreshDatabase` を適用するなどの共通設定 |
| `TestCase.php` | 基底クラス                                                   |

## フロントエンド

### resources/js/

| パス                              | 役割                                                                                                                            |
| --------------------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| `app.tsx`                         | エントリポイント。ページ名からレイアウトを振り分ける（`auth/*` は AuthLayout、`settings/*` は AppLayout + SettingsLayout など） |
| `pages/`                          | Inertia のページ。`Inertia::render('settings/profile')` が `pages/settings/profile.tsx` に対応                                  |
| `components/`                     | 再利用するコンポーネント                                                                                                        |
| `components/ui/`                  | shadcn/ui が生成したプリミティブ。lint とフォーマットの対象外                                                                   |
| `layouts/`                        | レイアウト                                                                                                                      |
| `hooks/`                          | カスタムフック                                                                                                                  |
| `lib/utils.ts`                    | `cn()`（クラス名の結合）                                                                                                        |
| `types/`                          | 共有の型定義                                                                                                                    |
| `actions/` `routes/` `wayfinder/` | **自動生成。編集しないでください**                                                                                              |

`@/` は `resources/js/` のエイリアスです（`tsconfig.json` で定義）。

自動生成の3つは [Wayfinder](https://github.com/laravel/wayfinder) が Vite のビルド時に
PHP のルート定義から作ります。これのおかげでルートやコントローラのアクションを
TypeScript から型付きで呼べます。gitignore 済みなので clone 直後は存在せず、
`npm run dev` か `npm run build` で生成されます。

### resources/css/

`app.css` の1枚だけです。Tailwind v4 なので**設定もこのファイルの中**（`@theme` ブロック）にあります。
`tailwind.config.js` は存在しません。

### resources/views/

`app.blade.php` の1枚だけです。Inertia が差し込む要素を持つルートテンプレートで、
画面を作るときに触ることはまずありません。

### public/

| パス                                                | 役割                       |
| --------------------------------------------------- | -------------------------- |
| `index.php`                                         | すべてのリクエストの入り口 |
| `build/`                                            | Vite のビルド成果物        |
| `favicon.*` / `apple-touch-icon.png` / `robots.txt` | 静的ファイル               |

## ルート直下の設定ファイル

| ファイル              | 役割                                                                                     |
| --------------------- | ---------------------------------------------------------------------------------------- |
| `composer.json`       | PHP の依存と `composer setup` / `dev` / `test` などのスクリプト                          |
| `package.json`        | JS の依存と `dev` / `build` / `check` スクリプト                                         |
| `vite.config.ts`      | ビルド設定に加えて、**lint とフォーマットの設定もここ**。                                |
| `tsconfig.json`       | TypeScript の設定。`@/*` → `resources/js/*`                                              |
| `components.json`     | shadcn/ui の設定（生成先とエイリアス）                                                   |
| `phpstan.neon`        | 静的解析。Larastan、level 7                                                              |
| `pint.json`           | PHP のフォーマッタ。laravel プリセット                                                   |
| `phpunit.xml`         | テストスイートの定義と、テスト実行時の環境変数                                           |
| `compose.yaml`        | Docker（Laravel Sail）のサービス定義                                                     |
| `.env.example`        | `.env` の雛形                                                                            |
| `.npmrc`              | `ignore-scripts=true`。`npm install` で postinstall スクリプトを実行しないようにする設定 |
| `pnpm-workspace.yaml` | スターターキット由来。無視して構いません                                                 |

CI の定義は `.github/workflows/tests.yml` です。

## 自動生成されるもの

以下は生成物です。編集しても次のインストールやビルドで上書きされます。

| パス                                           | 生成するもの                                 |
| ---------------------------------------------- | -------------------------------------------- |
| `vendor/`                                      | `composer install`                           |
| `node_modules/`                                | `npm install`                                |
| `public/build/`                                | `npm run build`                              |
| `bootstrap/ssr/`                               | SSR ビルド                                   |
| `bootstrap/cache/`                             | フレームワーク                               |
| `storage/framework/` `storage/logs/`           | フレームワークのキャッシュ・セッション・ログ |
| `resources/js/actions/` `routes/` `wayfinder/` | Wayfinder（Vite のビルド時）                 |
