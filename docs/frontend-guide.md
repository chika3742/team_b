---
title: フロントエンド開発ガイド
nav_order: 4
---

# {{ page.title }}

画面（React + Inertia）を作るときの手順とお作法をまとめたものです。

<details open markdown="block">
  <summary>
    目次
  </summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

## 用語

このガイドで断りなく使う言葉です。知っているものは読み飛ばしてください。

| 用語                | 説明                                                                                                            |
| ------------------- | --------------------------------------------------------------------------------------------------------------- |
| Inertia             | Laravel（サーバー）と React（画面）をつなぐ仕組み。API を作らなくても、サーバーが用意したデータが画面に届きます |
| SPA                 | 画面を移動してもページ全体を読み込み直さない作り。Inertia がこれを実現しています                                |
| コンポーネント      | 画面の部品。React では関数1つが部品1つです                                                                      |
| ページ              | `resources/js/pages/` 以下の1ファイル。1ファイルが1画面に対応します                                             |
| props               | 外から渡されるデータ。ページはサーバーから、部品は使う側から受け取ります                                        |
| レイアウト          | サイドバーやヘッダーなど、複数の画面で共通する枠                                                                |
| state               | 画面が一時的に覚えておく値。入力途中の文字や、開いているタブなど                                                |
| マウント            | コンポーネントが画面に置かれること。取り除かれるのがアンマウント。再マウントされると state は初期値に戻ります   |
| フック              | `useXxx` という名前の関数。React の機能を借りるときに呼びます                                                   |
| TypeScript / TSX    | 型の付いた JavaScript。TSX はその中に HTML のような書き方（JSX）を混ぜられるファイルです                        |
| 型                  | 「ここは文字列」「ここは数値の配列」という決めごと。合っていないとビルド時に教えてくれます                      |
| ルート              | URL とサーバー側の処理の対応。`/books` を開いたら何が動くか、という設定です                                     |
| Wayfinder           | Laravel のルートから TypeScript の関数を自動生成するツール。URLの直書きを防ぎます                               |
| Vite                | TSX や CSS をブラウザが読める形に変換する道具。`npm run dev` で動いているのがこれです                           |
| ホットリロード      | ファイルを保存した瞬間に、ブラウザの表示へ反映される仕組み                                                      |
| バリデーション      | 入力内容の検査。サーバー側で行い、結果が `errors` として画面に返ります                                          |
| lint / フォーマッタ | 書き方の問題を見つける道具 / 字下げなどを自動で整える道具                                                       |

## 全体像 {#overview}

1つのURLを開いたとき、何がどの順で動くかです。

```
ブラウザ                                    /books を開く
  → routes/web.php                          どのURLがどの処理に対応するか
  → app/Http/Controllers/BookController.php  データベースから蔵書を集める
  → Inertia::render('books/index', [...])    ページ名と props を返す
  → resources/js/pages/books/index.tsx       props を受け取って描画する ← ここが担当範囲
```

React 側から `fetch` で API を叩くことは**ありません**。画面に必要なデータはコントローラが
props として渡し、ページコンポーネントが引数で受け取ります。
逆にデータを送るときは `<Form>` を使い、そのまま次の画面の props が返ってきます。

つまり普段触るのは `resources/js/` の中だけです。
ただしルート（URL）の追加だけはバックエンド側のファイルなので、そこは次の節を見てください。

## 画面を1枚つくる

例として書籍検索画面（`/books`）を作ります。

### 1. ルートを足す

データ取得がまだ無い画面は `Route::inertia()` だけで表示できます。

```php
// routes/web.php
Route::inertia('books', 'books/index')->name('books.index');
```

第1引数が URL、第2引数が `resources/js/pages/` 以下のパス（拡張子なし）です。
コントローラが必要になったら後で差し替えます。

```php
Route::get('books', [BookController::class, 'index'])->name('books.index');
```

> [!IMPORTANT]
> ルートを足したら `npm run dev` を動かし直す（または `php artisan wayfinder:generate` を実行する）
> と、TypeScript から使えるルート関数が生成されます。[画面遷移とURL](#navigation)を参照してください。

### 2. ページを作る

```tsx
// resources/js/pages/books/index.tsx
import { Head } from '@inertiajs/react';

export default function BooksIndex() {
    return (
        <>
            <Head title="蔵書検索" />

            <div className="p-4">
                <h1 className="text-2xl font-semibold">蔵書検索</h1>
            </div>
        </>
    );
}
```

決まりごとは3つだけです。

- **ファイル名は kebab-case**（`book-detail.tsx`）。ディレクトリも同様です
- **default export** で、コンポーネント名は PascalCase
- 先頭に `<Head title="..." />` を置く。ブラウザのタブに「蔵書検索 - アプリ名」と出ます

これで <http://localhost:8000/books> に表示されます。

### 3. レイアウトを確認する

ページ名からレイアウトが自動で決まります。`books/index` は既定値に当たるので、
サイドバー付きの `AppLayout` が付きます。

| ページ名     | 適用されるレイアウト             |
| ------------ | -------------------------------- |
| `welcome`    | なし（素の画面）                 |
| `auth/*`     | `AuthLayout`（中央寄せのカード） |
| `settings/*` | `AppLayout` + `SettingsLayout`   |
| それ以外     | `AppLayout`（サイドバー付き）    |

利用者向けの画面をサイドバー無しで作りたい、といった場合は `resources/js/app.tsx` の
`switch` に分岐を足します。

```tsx
// resources/js/app.tsx
case name.startsWith('books/'):
    return PublicLayout;
```

## レイアウト

レイアウトはページを移動してもマウントされたままで、中身のページだけが入れ替わります。
そのためレイアウトに渡す値は props ではなく、**ページコンポーネントの静的プロパティ**で指定します。

```tsx
export default function BooksIndex() {
    /* ... */
}

// AppLayout のパンくず
BooksIndex.layout = {
    breadcrumbs: [{ title: '蔵書検索', href: index() }],
};
```

ページの props から組み立てたいときは関数にできます。

```tsx
BookShow.layout = (props) => ({
    breadcrumbs: [
        { title: '蔵書検索', href: index() },
        { title: props.book.title, href: show(props.book.id) },
    ],
});
```

表示中に動的に変えたいときだけ `setLayoutProps()` を使います。まずは静的プロパティで足ります。

| レイアウト       | ファイル                      | 受け取るprops           |
| ---------------- | ----------------------------- | ----------------------- |
| `AppLayout`      | `layouts/app-layout.tsx`      | `breadcrumbs`           |
| `AuthLayout`     | `layouts/auth-layout.tsx`     | `title` / `description` |
| `SettingsLayout` | `layouts/settings/layout.tsx` | なし                    |

## データを受け取る

コントローラの `Inertia::render()` の第2引数が、そのままページの引数になります。

```php
// app/Http/Controllers/BookController.php
return Inertia::render('books/index', [
    'books' => $books,
    'keyword' => $request->string('keyword'),
]);
```

```tsx
// resources/js/pages/books/index.tsx
type Book = {
    id: number;
    title: string;
    author: string;
};

type Props = {
    books: Book[];
    keyword: string;
};

export default function BooksIndex({ books, keyword }: Props) {
    return (
        <ul>
            {books.map((book) => (
                <li key={book.id}>{book.title}</li>
            ))}
        </ul>
    );
}
```

型は各ページに直接書いて構いません。複数の画面で使う型だけ `resources/js/types/` に置きます。

### 全ページ共通の props

ログイン中のユーザーなど、どのページでも使える値は `usePage()` で取ります。
中身は `app/Http/Middleware/HandleInertiaRequests.php` の `share()` で定義されています。

```tsx
import { usePage } from '@inertiajs/react';
import type { Auth } from '@/types';

const { auth } = usePage<{ auth: Auth }>().props;

auth.user.name;
```

| キー          | 中身                                          |
| ------------- | --------------------------------------------- |
| `auth.user`   | ログイン中のユーザー（未ログインなら `null`） |
| `name`        | アプリ名                                      |
| `sidebarOpen` | サイドバーの開閉状態                          |

## 画面遷移とURL {#navigation}

URL は文字列で書かず、[Wayfinder](https://github.com/laravel/wayfinder) が PHP のルート定義から
生成した関数を import して使います。ルートのURLが変わっても、画面側を直す必要がなくなります。

```tsx
// ✅ こう書く
import { index, show } from '@/routes/books';

<Link href={index()}>蔵書検索</Link>
<Link href={show(book.id)}>{book.title}</Link>

// ❌ 避ける
<Link href="/books">蔵書検索</Link>
<Link href={`/books/${book.id}`}>{book.title}</Link>
```

| import 元       | 中身                                                          |
| --------------- | ------------------------------------------------------------- |
| `@/routes/...`  | 名前付きルート（`books.index` → `@/routes/books` の `index`） |
| `@/actions/...` | コントローラのアクション（`BookController.index`）            |

> [!NOTE]
> **関数が見つからないときは生成し直してください。** 生成物は
> `resources/js/{actions,routes,wayfinder}/` にあり、gitignore 済みです。
> バックエンド担当がルートを足した直後は `git pull` しただけでは増えないので、
> `npm run dev` を起動し直すか `php artisan wayfinder:generate` を実行します。
> これらのファイルを直接編集しても、次のビルドで上書きされます。

`<Link>` はページ全体をリロードせずに遷移します。外部サイトへのリンクだけ素の `<a>` を使ってください。
クエリ文字列を付けたいときは `index({ query: { keyword: 'Laravel' } })` と書けます。

## フォーム

`<Form>` に Wayfinder のフォーム定義を展開するのが基本形です。
入力値の state を自分で持つ必要はありません。

```tsx
import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/reservations';

<Form {...store.form()} className="space-y-6">
    {({ processing, errors }) => (
        <>
            <div className="grid gap-2">
                <Label htmlFor="receive_facility_id">受け取り館</Label>
                <Input
                    id="receive_facility_id"
                    name="receive_facility_id"
                    required
                />
                <InputError message={errors.receive_facility_id} />
            </div>

            <Button disabled={processing}>予約する</Button>
        </>
    )}
</Form>;
```

- `name` 属性がそのままサーバーに送るキーになります
- `errors` はサーバーのバリデーションエラー。キーは入力の `name` と同じ
- `processing` は送信中。ボタンを `disabled` にして二重送信を防ぎます
- 初期値は `value` ではなく `defaultValue` で入れます（非制御コンポーネントのため）

| オプション                           | 用途                             |
| ------------------------------------ | -------------------------------- |
| `resetOnSuccess={['password']}`      | 成功時に指定フィールドを空に戻す |
| `options={{ preserveScroll: true }}` | 送信後にスクロール位置を保つ     |

検索フォームのように GET で送る場合も同じです（`{...index.form()}`）。
実例は `pages/auth/login.tsx` と `pages/settings/profile.tsx` が分かりやすいです。

## トースト通知

サーバー側で `Inertia::flash('toast', ...)` すると、画面右上に自動で出ます。
フロント側で書くことはありません（`components/ui/sonner.tsx` が受け取っています）。

```php
Inertia::flash('toast', ['type' => 'success', 'message' => '予約しました。']);

return to_route('reservations.index');
```

`type` は `success` / `info` / `warning` / `error` の4つです。

## UI コンポーネント

画面の部品は [Figma](https://www.figma.com/design/ajdUuKqg5j7bABAvxTmQIs/%E5%9B%B3%E6%9B%B8%E9%A4%A8%E4%BA%88%E7%B4%84%E3%82%B7%E3%82%B9%E3%83%86%E3%83%A0?node-id=0-1) のデザインに合わせて**自分たちで作ります**。
`resources/js/components/` に今入っているもの（`heading`、`input-error`、`text-link` など）は
スターターキット由来のサンプルです。参考にするのは自由ですが、デザインに合わなければ
書き換えても消しても構いません。

### どこに置くか

| 使う範囲     | 置き場所                              |
| ------------ | ------------------------------------- |
| その画面だけ | ページファイルの中にそのまま書く      |
| 複数の画面   | `resources/js/components/` に切り出す |

最初から切り出す必要はありません。2つ目の画面で同じものが要るようになった時点で移せば十分です。

> [!WARNING]
> **`components/ui/` には置かないでください。** ここは shadcn/ui が生成したコードの置き場所で、
> lint とフォーマットの対象外に設定されています（`vite.config.ts`）。
> 自作したものは `components/` 直下に置いてください。

### 書き方

- ファイル名は kebab-case（`book-card.tsx`）、`export default` で1つ出す
- props の型は引数に直接書く
- `className` を受け取って `cn()` で結合する。余白は呼び出し側で決められるほうが使い回せます

```tsx
// resources/js/components/book-card.tsx
import { cn } from '@/lib/utils';

export default function BookCard({
    title,
    author,
    isAvailable,
    className,
}: {
    title: string;
    author: string;
    isAvailable: boolean;
    className?: string;
}) {
    return (
        <article
            className={cn(
                'bg-card rounded-lg border p-4',
                !isAvailable && 'opacity-60',
                className,
            )}
        >
            <h3 className="font-medium">{title}</h3>
            <p className="text-muted-foreground text-sm">{author}</p>
        </article>
    );
}
```

見た目のバリエーション（サイズ違い、色違い）が3つ4つと増えてきたら、`if` を並べる代わりに
[cva](https://cva.style/)（導入済み）でまとめられます。`components/ui/button.tsx` が実例です。

### 自作しなくていいもの

ダイアログ、セレクト、ドロップダウン、ツールチップのように
**キーボード操作とフォーカス管理が必要な部品**は、ゼロから作らないでください。
自作するとタブ移動や Esc が効かない画面になります。

[Radix UI](https://www.radix-ui.com/primitives) が導入済みで、その薄いラッパーが
`components/ui/` にあります。見た目は `className` で上書きできるので、これを土台にするのが早いです。
現在あるもの:

`alert` `avatar` `badge` `breadcrumb` `button` `card` `checkbox` `collapsible` `dialog`
`dropdown-menu` `icon` `input` `label` `navigation-menu` `select` `separator` `sheet`
`sidebar` `skeleton` `sonner` `spinner` `toggle` `toggle-group` `tooltip`

未導入のプリミティブはコンテナ内で追加できます。

```bash
npx shadcn@latest add table
```

### アイコン

[lucide-react](https://lucide.dev/icons/) を使います。

```tsx
import { Search } from 'lucide-react';

<Search className="size-4" />;
```

## スタイル

Tailwind CSS v4 です。**`tailwind.config.js` はありません**。
設定は `resources/css/app.css` の `@theme` ブロックにあります。

色は生の色名ではなく**セマンティックなトークン**を使ってください。
ライト／ダークの切り替えが自動で効きます。

```tsx
// ✅ こう書く
<p className="text-muted-foreground text-sm">貸出中</p>

// ❌ ダークモードで読めなくなる
<p className="text-sm text-gray-500">貸出中</p>
```

| クラス                                   | 用途                 |
| ---------------------------------------- | -------------------- |
| `bg-background` / `text-foreground`      | ページの地と文字     |
| `bg-card` / `text-card-foreground`       | カードの中           |
| `bg-primary` / `text-primary-foreground` | 主要なボタンなど     |
| `text-muted-foreground`                  | 補足テキスト         |
| `border-border`                          | 罫線                 |
| `bg-destructive`                         | 削除など破壊的な操作 |

条件付きでクラスを付けるときは `cn()` を使います。

```tsx
import { cn } from '@/lib/utils';

<div className={cn('rounded-lg border p-4', isReserved && 'opacity-50')} />;
```

## バックエンドが無いとき

画面を先に作るときは、`Route::inertia()` で表示だけできる状態にして、
データはページファイルの中に仮で置きます。

```tsx
// TODO: バックエンド実装後に props へ差し替える
const books: Book[] = [
    { id: 1, title: 'リーダブルコード', author: 'Dustin Boswell' },
    { id: 2, title: 'テスト駆動開発', author: 'Kent Beck' },
];
```

型（`type Book = ...`）だけは[ER図](https://app.notion.com/p/38e006b1b9d48052b782fc5c8c2715c2)の
カラム名に合わせておくと、繋ぎ込みのときに変更が要りません。
仮データは引数の props に置き換えるだけで済むよう、コンポーネントの外に定数として置いてください。

## 書き終えたら

コミット前にコンテナ内で実行します（ホストからは `sail` を頭に付けてください）。

```bash
npm run check:fix     # oxlint + oxfmt。自動修正できるものは直る
npm run types:check   # TypeScript の型チェック
```

CI でも同じものが走るので、ここが通らないと PR はマージできません。

## トラブルシューティング

### 画面を変更したのに反映されない

`npm run dev` が動いていますか。起動していない場合は `public/build` のビルド済みアセットが
表示されるため、変更は `npm run build` するまで反映されません。

### `@/routes/...` が見つからない、と言われる

Wayfinder の生成物がまだありません。`npm run dev` か `php artisan wayfinder:generate` を
実行してください。

### `Unable to locate file in Vite manifest`

同じく `npm run dev` か `npm run build` を実行してください。

### フォームを送ったのに何も起きない

`<Form>` の中の入力欄に `name` 属性が付いているか確認してください。
また、エラーが出ているのに気付いていない場合があります。
`{({ errors }) => ...}` の `errors` を一時的に `console.log` すると分かります。

### `errors` の型が合わない

`errors` のキーはサーバーのバリデーションルール次第なので、TypeScript は中身を知りません。
`errors.foo` はそのまま書いて問題ありません。

### ページが真っ白になる

ブラウザの開発者ツールのコンソールを見てください。多くは `undefined` のプロパティ参照です。
props がまだ渡っていない、配列が `null` で返ってきている、あたりが原因になりがちです。
