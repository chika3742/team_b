# 開発環境セットアップ

[Laravel Sail](https://laravel.com/docs/sail) を使って、ローカル開発環境を完全に Docker 内で構築する手順です。
**用意するのは Docker だけ**で、ホストに PHP・Node・MySQL をインストールする必要はありません。

## 目次

- [必要なもの](#必要なもの)
- [セットアップ](#セットアップ)
    - [1. clone して .env を作る](#1-clone-して-env-を作る)
    - [2. vendor を用意する](#2-vendor-を用意する)
    - [3. コンテナを起動する](#3-コンテナを起動する)
    - [4. セットアップを仕上げる](#4-セットアップを仕上げる)
    - [5. フロントエンドを起動する](#5-フロントエンドを起動する)
- [DevContainer を使う場合](#devcontainer-を使う場合)
- [AI エージェントを使用する場合](#ai-エージェントを使用する場合)
- [何がどこで動くか](#何がどこで動くか)
- [よく使うコマンド](#よく使うコマンド)
- [テストとコード品質](#テストとコード品質)
- [ディレクトリ構成](#ディレクトリ構成)
- [トラブルシューティング](#トラブルシューティング)

## 必要なもの

- **Docker** — [OrbStack](https://orbstack.dev/) または Docker Desktop。これ以外はすべてコンテナ内で動きます
- **Git**
- **エディタの DevContainer 連携**（任意）— VS Code + Dev Containers 拡張、または JetBrains IDE。[DevContainer を使う場合](#devcontainer-を使う場合)を参照

## セットアップ

プロジェクトルートで実行します。手順 1〜3 はホスト側でしか実行できません。
手順 4 以降はコンテナ内の処理で、ホストからは `./vendor/bin/sail` 経由で呼び出します。
DevContainer のターミナルなど、すでにコンテナ内にいる場合は `sail` を付けない形で実行してください
（対応は[アプリを操作する](#アプリを操作する)を参照）。

### 1. clone して .env を作る

```bash
git clone https://github.com/chika3742/team_b.git
cd team_b
cp .env.example .env
```

`.env` は**この次の手順より前に**存在している必要があります。`compose.yaml` の
`${WWWGROUP}` `${WWWUSER}` `${APP_PORT}` `${DB_PASSWORD}` を解決するために、
Docker Compose 自身が `.env` を読むためです。

`.env.example` の初期値は Sail 構成に合わせてあるので、編集は不要です。
`APP_KEY` は空のままにしてください（手順 4 で生成されます）。

### 2. vendor を用意する

`compose.yaml` はアプリのイメージを `./vendor/laravel/sail/runtimes/8.5` からビルドしますが、
clone 直後はこのディレクトリが存在しません。使い捨てのコンテナで Composer の依存パッケージを一度だけインストールします。

```bash
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" \
  -w /var/www/html \
  composer:2 \
  composer install --ignore-platform-reqs --no-scripts

# 使い捨てのイメージを削除する場合
docker rmi composer:2
```

Sail 以外のコンテナを使うのはこの手順だけで、実行するのも最初の一度きりです。

### 3. コンテナを起動する

```bash
./vendor/bin/sail up -d
```

初回は PHP イメージのビルドで数分かかります。その後 MySQL がデータディレクトリを初期化するのに
さらに 15〜30 秒ほどかかります。`./vendor/bin/sail ps` で状態を確認できますが、
初期化中の MySQL は `starting` ではなく `unhealthy` と表示されることがあります
（healthcheck に `start_period` が設定されていないため）。異常ではないので、そのまま次に進んでください。

なおこの時点ではまだアプリは動きません。`APP_KEY` の生成もマイグレーションも次の手順なので、
コンテナのログには起動エラーが出ます。手順 4 が終われば解消します。

### 4. セットアップを仕上げる

```bash
./vendor/bin/sail composer setup   # コンテナ内からは composer setup
```

`composer install`、`APP_KEY` の生成、マイグレーション、npm パッケージのインストール、
フロントエンドのビルドまでを一括で行います。

MySQL の初期化が終わる前に実行すると、マイグレーションが接続エラーで落ちます。
その場合は数十秒待ってもう一度同じコマンドを実行してください。

### 5. フロントエンドを起動する

```bash
./vendor/bin/sail npm run dev   # コンテナ内からは npm run dev
```

これで **<http://localhost:8000>** にアクセスできます。

## DevContainer を使う場合

`.devcontainer/devcontainer.json` は `compose.yaml` の `laravel.test` サービスにアタッチする設定です。
エディタがアプリと同じコンテナの中で動きます。

**上の手順 1〜3 はホスト側で先に実行する必要があります。**
`vendor/laravel/sail/runtimes/8.5/Dockerfile` が無いと DevContainer 自体がビルドできないためです。

その後、コンテナ内でプロジェクトを開きます。

- **VS Code** — _Dev Containers: Reopen in Container_
- **JetBrains** — `.devcontainer/devcontainer.json` を開いてガター（行番号の横）のアクション（Create Dev Container and Mount Sources...）から起動。

コンテナ内では `./vendor/bin/sail` を付ける必要はなく、`composer setup` や `npm run dev`、
`php artisan ...` をそのまま実行できます。

## AI エージェントを使用する場合

AI エージェント向けの設定ファイルは**Git管理されていません**。 使うかどうか、どのエージェントを使うかは各自の判断に任せます。

### 設定ファイルを生成する

[Laravel Boost](https://github.com/laravel/boost) が、このアプリ向けのガイドライン・Skill・
MCP 設定をまとめて生成します。コンテナ内で実行してください。

```bash
php artisan boost:install
```

対話形式で、使うエージェント・導入する Skill・MCP 設定を選べます。

### MCP サーバーはコンテナ内で動かす必要があります

生成される MCP 設定は `php artisan boost:mcp` を起動します。PHP が必要なので、
**エージェント自体もコンテナ内で動かしてください**。ホスト側から使うと MCP サーバーが起動できず、
`database-query` や `search-docs` といった Boost のツールが一切使えません。

### Claude Code をコンテナに入れる

ネイティブインストーラを使ってください。

```bash
# DevContainerを起動した（開いた）状態でホストから
docker exec -u root "$(docker ps -qf name=laravel.test)" chown -R sail:sail /home/sail/.claude

# コンテナ内で
curl -fsSL https://claude.ai/install.sh | bash
```

インストール先が `~/.local/bin` なので、**コンテナを作り直すと消えます**。
`sail stop` → `sail up -d` や「Reopen in Container」では残りますが、
`sail down` や「Rebuild Container」の後は入れ直してください。

## 何がどこで動くか

`sail up` で起動するコンテナは 2 つです。

| コンテナ       | 中身                                                                                                         |
| -------------- | ------------------------------------------------------------------------------------------------------------ |
| `laravel.test` | PHP 8.5 / Node 24 / Composer。起動時に supervisor が `artisan serve --host=0.0.0.0 --port=80` を常駐させます |
| `mysql`        | MySQL 8.4。初回起動時に `laravel` と `testing` の 2 つの DB を作成します                                     |

**バックエンドは起動コマンド不要です**。コンテナが上がった時点ですでに動いています。
一方 **Vite は自動起動しません**。ホットリロードを使いたいときは `npm run dev` を自分で実行してください。
起動していなくても `public/build` のビルド済みアセットで画面は表示されます。

**ポート**

| ホスト側 | 用途             | 変更する変数      |
| -------- | ---------------- | ----------------- |
| 8000     | アプリケーション | `APP_PORT`        |
| 5173     | Vite dev server  | `VITE_PORT`       |
| 3306     | MySQL            | `FORWARD_DB_PORT` |

**DB 接続情報** — database `laravel` / user `sail` / password `password`。
シェルに入るにはホストからは `./vendor/bin/sail mysql`、コンテナ内からは `php artisan db` です。
GUI クライアントからは `127.0.0.1:3306` で接続できます。

## よく使うコマンド

`./vendor/bin/sail` は `docker compose` のラッパー（bash スクリプト）なので、
ホストに PHP が無くても動きます。毎回打つのは長いので alias を張るのが一般的です。

```bash
alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'
```

以下はこの alias 前提の表記です。

### コンテナを操作する（ホストからのみ）

`sail` は `docker compose` を呼ぶスクリプトで、コンテナ内には docker CLI がありません。
これらはホスト側でしか実行できません。

| コマンド                   | 内容                                              |
| -------------------------- | ------------------------------------------------- |
| `sail up -d` / `sail stop` | コンテナの起動 / 停止                             |
| `sail ps`                  | コンテナの状態を表示                              |
| `sail shell`               | アプリコンテナ内の bash に入る                    |
| `sail down`                | コンテナを**削除**（`stop` と違い中身が消えます） |

### アプリを操作する

同じ処理を、ホストからは `sail` 経由で、コンテナ内では直接実行します。

| 内容                                                | ホストから                          | コンテナ内                         |
| --------------------------------------------------- | ----------------------------------- | ---------------------------------- |
| Vite dev server（ホットリロード）                   | `sail npm run dev`                  | `npm run dev`                      |
| 本番用アセットビルド                                | `sail npm run build`                | `npm run build`                    |
| マイグレーション実行                                | `sail artisan migrate`              | `php artisan migrate`              |
| DB を作り直してシードを流す                         | `sail artisan migrate:fresh --seed` | `php artisan migrate:fresh --seed` |
| アプリのコンテキストで REPL（対話シェル）           | `sail artisan tinker`               | `php artisan tinker`               |
| MySQL シェル                                        | `sail mysql`                        | `php artisan db`                   |
| サーバー・キューワーカー・ログ・Vite をまとめて起動 | `sail composer dev`                 | `composer dev`                     |

`composer dev` はキューワーカーとログも一緒に見たいときに便利です。
ただしこのコマンドは `artisan serve` も起動するため、supervisor が既に動かしているものと二重になります。
実害はありませんが、そちらのポートはホストに公開されていません。

## テストとコード品質

```bash
# ホストから                     # コンテナ内から
sail test                        # php artisan test               … Pest のテストスイート
sail test --filter=UserTest      # php artisan test --filter=...  … 特定のテストだけ
sail composer test               # composer test                  … Pint + PHPStan + テスト
sail composer ci:check           # composer ci:check              … CI と同じ内容をすべて
```

| ツール                                                | 設定ファイル     | ホストから                                      | コンテナ内                            |
| ----------------------------------------------------- | ---------------- | ----------------------------------------------- | ------------------------------------- |
| [Pest](https://pestphp.com/)                          | `phpunit.xml`    | `sail test`                                     | `php artisan test`                    |
| [Pint](https://laravel.com/docs/pint)（フォーマッタ） | `pint.json`      | `sail composer lint`                            | `composer lint`                       |
| [PHPStan](https://phpstan.org/) / Larastan level 7    | `phpstan.neon`   | `sail composer types:check`                     | `composer types:check`                |
| oxlint + oxfmt（vite-plus 同梱）                      | `vite.config.ts` | `sail npm run check` / `sail npm run check:fix` | `npm run check` / `npm run check:fix` |
| TypeScript                                            | `tsconfig.json`  | `sail npm run types:check`                      | `npm run types:check`                 |

**ローカルと CI ではテストの DB が異なります。**
ローカルは MySQL の `testing` データベースを使います。CI はジョブレベルの環境変数で
`DB_CONNECTION=sqlite` / `DB_DATABASE=:memory:` を指定しており、MySQL サービスを立てずに動きます
（実際の環境変数が `.env` と `phpunit.xml` のどちらよりも優先されるため）。
そのため **MySQL 固有の挙動は、ローカルでテストを実行したときにしか分かりません**。

## ディレクトリ構成

主要なフォルダーとファイルの役割は[ディレクトリ構成](directory-structure.md)にまとめてあります。

## トラブルシューティング

### `sail up` のビルド中に `groupadd` のエラーで落ちる

`.env` に `WWWGROUP` / `WWWUSER` がありません。Compose が空文字を渡してビルドが失敗します。
`.env.example` をコピーし直してください。

### `sail up` 直後に `SQLSTATE[HY000] [2002] Connection refused`

MySQL がまだデータディレクトリを初期化中です。`compose.yaml` に healthcheck はありますが、
アプリ側がそれを待つ設定にはなっていないため、起動直後の数十秒は接続に失敗します。
`sail ps` で状態を確認し、少し待ってから再実行してください。

### `npm run dev` がネイティブモジュール（`rollup` / `oxide` / `lightningcss` / `vp`）で落ちる

別プラットフォーム向けの `node_modules` が入っています。コンテナを起動する前にホストで
`npm install` を実行した場合に起きます。`node_modules` は bind mount されているので、
コンテナからもホスト側のバイナリが見えてしまいます。コンテナ内で入れ直してください。

```bash
sail shell   # すでにコンテナ内にいる場合は不要
rm -rf node_modules && npm install
```

### `Unable to locate file in Vite manifest`

ビルド済みアセットがありません。`sail npm run dev` か `sail npm run build`
（コンテナ内からは `npm run dev` / `npm run build`）を実行してください。

### 8000 / 5173 / 3306 番ポートが既に使われている

`.env` の `APP_PORT` / `VITE_PORT` / `FORWARD_DB_PORT` を変更して `sail up -d` し直してください。

### `.env` を編集したのに反映されない

Laravel は一度読み込んだ環境変数を上書きしません。同じキーが複数行あると**先に書かれたほうが有効になり**、
さらに実際の環境変数が `.env` より優先されます。
行を追記するのではなく既存の行を書き換えて、`sail restart` してください。
