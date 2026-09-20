---
title: バックエンド開発ガイド
nav_order: 5
---

# {{ page.title }}

Laravel 側（ルート・コントローラ・モデル・テスト）を書くときの手順とお作法をまとめたものです。

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

| 用語               | 説明                                                                                     |
| ------------------ | ---------------------------------------------------------------------------------------- |
| Laravel            | PHP のフレームワーク（土台）。ルーティング・DB・認証などが最初から入っています           |
| Artisan            | `php artisan ...` で呼ぶコマンド群。ファイルの生成やDBの操作に使います                   |
| ルート             | URL とどの処理を結びつけるかの設定。`routes/web.php` に書きます                          |
| コントローラ       | リクエストを受け取り、データを集めて、画面に渡す係                                       |
| ミドルウェア       | コントローラの前に通る共通処理。「ログインしていない人を弾く」など                       |
| モデル             | 1つのテーブルに対応する PHP のクラス。`Book` クラスが `books` テーブルにあたります       |
| Eloquent           | SQL を書かずに、PHP のメソッドでDBを読み書きする仕組み（ORM）                            |
| マイグレーション   | テーブルの定義を PHP で書いたファイル。実行するとDBに反映されます                        |
| リレーション       | テーブル同士のつながり。「1冊の蔵書が複数の現物を持つ」など                              |
| 外部キー           | 別のテーブルの行を指す列。`book_items.book_id` が `books.id` を指します                  |
| ファクトリ         | テスト用のデータを作る仕組み。項目を全部書かなくてもモデルを1行で用意できます            |
| シーダー           | 動作確認用の初期データをDBに流し込む処理                                                 |
| Enum               | 決まった値しか取らない型。`status` が「貸出可能・貸出中・運搬中」のどれか、など          |
| バリデーション     | 入力内容の検査。このプロジェクトではフォームリクエストに書きます                         |
| フォームリクエスト | バリデーションのルールだけを書いた専用クラス                                             |
| ポリシー           | 「この人がこの1件を操作してよいか」を判定するクラス。モデル1つにつき1つ作ります          |
| トレイト           | 複数のクラスで使い回すメソッドのまとまり。`use` で取り込みます                           |
| 属性               | `#[Fillable([...])]` のような `#[]` の記法。クラスに設定を付けます                       |
| PHPDoc             | `/** ... */` のコメント。型を書いておくとエディタと PHPStan が理解します                 |
| N+1                | 一覧を表示するときに、行数ぶんのクエリが余計に走ってしまう問題                           |
| Fortify            | ログイン・登録・パスワード再設定を用意してくれる Laravel のパッケージ                    |
| Inertia            | Laravel と React をつなぐ仕組み。`Inertia::render()` で返した値が画面の props になります |
| 静的解析 / PHPStan | プログラムを動かさずに、型の矛盾やミスを見つける道具                                     |
| Pint               | PHP のコードを自動で整形する道具                                                         |
| Pest               | テストを書く・動かすための道具                                                           |

## 全体像

1つのリクエストが通る道です。画面側から見た流れは[フロントエンド開発ガイド](frontend-guide.md#overview)にあります。

```
リクエスト
  → routes/web.php                     URLとコントローラの対応。ミドルウェアもここ
  → app/Http/Requests/...Request.php   バリデーション（フォーム送信のとき）
  → app/Http/Controllers/...            データを集める・保存する
  → app/Models/...                      Eloquent でDBを読み書き
  → Inertia::render() / to_route()      ページを返す・リダイレクトする
```

コントローラは**集めて渡すだけ**にして、業務ロジックはモデルに寄せます。
Blade はログイン画面も含めて一切書きません。返すのは常に `Inertia::render()` かリダイレクトです。

使っているバージョンは Laravel 13 / Fortify 1.39 / Inertia Laravel 3.3 / Pest 5 です。
`composer show --direct` で確認できます。

## テーブルとモデルを作る

[ER図](https://app.notion.com/p/38e006b1b9d48052b782fc5c8c2715c2)と
[テーブル定義書](https://app.notion.com/p/3aa006b1b9d4802d916ae9a00045e2e2)が元になります。
1テーブルぶんをまとめて作るなら次の1行で足ります。

```bash
php artisan make:model Book -mf    # モデル + マイグレーション + ファクトリ
```

`-s` を足すとシーダーも、`--all` でポリシーやコントローラまで作られます。

> [!IMPORTANT]
> **主キーの名前は先に決めてください。** ER図は `book_id` `item_id` という書き方ですが、
> Laravel の既定は `id` で、外部キーは `books.id` を指す `book_id` です。
> 既定に合わせると `$table->id()` とリレーションの推論がそのまま効きます。
> ER図どおり `books.book_id` にする場合は、モデルごとに `$primaryKey` の指定が必要です。
> 以下の例は Laravel の既定（`id`）で書いています。

### 1. マイグレーション

```php
// database/migrations/xxxx_xx_xx_xxxxxx_create_book_items_table.php
public function up(): void
{
    Schema::create('book_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('book_id')->constrained()->cascadeOnDelete();
        $table->foreignId('owner_facility_id')->constrained('facilities');
        $table->foreignId('current_facility_id')->constrained('facilities');
        $table->string('book_item_no')->unique();
        $table->string('status');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('book_items');
}
```

押さえるところは4つです。

- **`constrained()` はテーブル名を推測します。** `book_id` なら `books` を見に行くので引数は要りません。
  `owner_facility_id` は `owner_facilities` を探しに行って失敗するので、`constrained('facilities')` と明示します
- **削除時の挙動を決めます。** 蔵書が消えたら現物も消す関係なら `cascadeOnDelete()`。
  施設のように消してほしくない側には付けません
- **ENUM 列は `string` にします。** MySQL の `enum()` 型は後から値を足すたびに `ALTER TABLE` が要ります。
  文字列で持って、PHP 側の [Enum](#enum) で値を縛るほうが変更に強いです
- **`down()` を書きます。** `migrate:rollback` で戻せないと、やり直しのたびに DB を作り直すことになります

外部キーを張ると多くの場合インデックスも一緒に作られます。**それとは別に、実際に検索する組み合わせ**
（例: `reservations` を `book_id` と `reserved_at` で絞る）にだけインデックスを足してください。

```php
$table->index(['book_id', 'reserved_at']);
```

まだ共有していないマイグレーションなら、書き換えて `php artisan migrate:fresh --seed` でやり直すのが早いです。
**`main` に入ったあとは書き換えず、新しいマイグレーションを追加**してください。
他の人の手元のDBは古いファイルの内容で作られているためです。

### 2. Enum {#enum}

`status` や `role` のように決まった値しか入らない列は PHP の Enum にします。

```bash
php artisan make:enum Enums/BookItemStatus
```

`Enums/` を付けずに実行すると `app/` 直下に作られます（`app/Enums/` が既にある場合のみ、
そちらに置かれます）。最初の1つだけパスを明示してください。

```php
// app/Enums/BookItemStatus.php
namespace App\Enums;

enum BookItemStatus: string
{
    case Available = 'available';
    case OnLoan = 'on_loan';
    case InTransit = 'in_transit';

    public function label(): string
    {
        return match ($this) {
            self::Available => '貸出可能',
            self::OnLoan => '貸出中',
            self::InTransit => '運搬中',
        };
    }
}
```

キーは TitleCase（`OnLoan`）、値はDBに入る文字列です。
`match` に新しい case を足し忘れると PHPStan が教えてくれます。

### 3. モデル

```php
// app/Models/BookItem.php
namespace App\Models;

use App\Enums\BookItemStatus;
use Database\Factories\BookItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $book_id
 * @property int $owner_facility_id
 * @property int $current_facility_id
 * @property string $book_item_no
 * @property BookItemStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['book_id', 'owner_facility_id', 'current_facility_id', 'book_item_no', 'status'])]
class BookItem extends Model
{
    /** @use HasFactory<BookItemFactory> */
    use HasFactory;

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function ownerFacility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'owner_facility_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BookItemStatus::class,
        ];
    }
}
```

`User` モデルにならってください。要点は次のとおりです。

| 書くもの               | 理由                                                                 |
| ---------------------- | -------------------------------------------------------------------- |
| `@property` の一覧     | エディタの補完が効き、PHPStan もカラムの型を把握できます             |
| `#[Fillable([...])]`   | このプロジェクトは `$fillable` プロパティではなく属性で書きます      |
| リレーションの戻り値型 | `BelongsTo` `HasMany` を必ず書きます。PHPStan level 7 が要求します   |
| `casts()`              | Enum・日付・真偽値はここで変換します。プロパティではなくメソッドです |

リレーション名から外部キーを推測できないとき（`ownerFacility` → `owner_facility_id`）は、
第2引数で明示します。

同じ絞り込みを何度も書くようならスコープにします。

```php
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

#[Scope]
protected function available(Builder $query): Builder
{
    return $query->where('status', BookItemStatus::Available);
}

// 使うとき
BookItem::available()->get();
```

### 4. ファクトリとシーダー

ファクトリはテストで必ず使います。ダミーデータではなく**テストの前提条件**だと思ってください。

```php
// database/factories/BookItemFactory.php
/**
 * @return array<string, mixed>
 */
public function definition(): array
{
    return [
        'book_id' => Book::factory(),
        'owner_facility_id' => Facility::factory(),
        'current_facility_id' => Facility::factory(),
        'book_item_no' => fake()->unique()->numerify('########'),
        'status' => BookItemStatus::Available,
    ];
}

/**
 * Indicate that the item is currently on loan.
 */
public function onLoan(): static
{
    return $this->state(fn (array $attributes) => [
        'status' => BookItemStatus::OnLoan,
    ]);
}
```

関連モデルは `Book::factory()` をそのまま値に入れれば、必要なぶんだけ一緒に作られます。
状態ちがいは `onLoan()` のような state メソッドにしておくと、テストが1行で済みます。

シーダーは `database/seeders/DatabaseSeeder.php` から呼びます。
動作確認用の施設5件や蔵書は、ここに入れておくとチーム全員が同じデータで確認できます。

```bash
php artisan migrate:fresh --seed
```

## 画面にデータを渡す

### ルート

```php
// routes/web.php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('books', [BookController::class, 'index'])->name('books.index');
    Route::get('books/{book}', [BookController::class, 'show'])->name('books.show');
});
```

- **名前（`->name()`）は必ず付けてください。** Wayfinder がこの名前から TypeScript の関数を作ります。
  `books.index` が `@/routes/books` の `index()` になります
- 名前は `index` / `show` / `create` / `store` / `edit` / `update` / `destroy` の標準に合わせます
- `{book}` と書くとモデルが自動で解決され、コントローラに `Book $book` が渡ります
- ログインが要る画面は `auth`、メール確認済みも要るなら `verified` を付けます
- ルートが増えて `web.php` が読みにくくなったら、`routes/settings.php` にならって分割します

### コントローラ

```bash
php artisan make:controller BookController
```

```php
// app/Http/Controllers/BookController.php
namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    /**
     * Show the book search page.
     */
    public function index(Request $request): Response
    {
        $keyword = $request->string('keyword')->toString();

        $books = Book::query()
            ->with('category')
            ->when($keyword !== '', fn ($query) => $query->where('title', 'like', "%{$keyword}%"))
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('books/index', [
            'books' => $books,
            'keyword' => $keyword,
        ]);
    }
}
```

- `Inertia::render()` の第1引数は `resources/js/pages/` 以下のパスです（`books/index` → `books/index.tsx`）
- 戻り値の型は必ず書きます。ページを返すなら `Inertia\Response`、リダイレクトなら `Illuminate\Http\RedirectResponse`
- メソッドの上に1行の PHPDoc を付けるのがこのプロジェクトの書き方です
- `paginate()` の結果はそのまま渡せます。件数・現在ページ・リンクを含んだ形で画面に届きます
- `withQueryString()` を付けると、2ページ目に行っても検索語が消えません

## フォームを受け取る

バリデーションは**コントローラに書かず**、フォームリクエストに分けます。

```bash
php artisan make:request ReservationStoreRequest
```

```php
// app/Http/Requests/ReservationStoreRequest.php
namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReservationStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'book_id' => ['required', 'integer', Rule::exists('books', 'id')],
            'receive_facility_id' => ['required', 'integer', Rule::exists('facilities', 'id')],
        ];
    }
}
```

```php
/**
 * Store a new reservation.
 */
public function store(ReservationStoreRequest $request): RedirectResponse
{
    $request->user()->reservations()->create([
        ...$request->validated(),
        'reserved_at' => now(),
    ]);

    Inertia::flash('toast', ['type' => 'success', 'message' => __('予約を受け付けました。')]);

    return to_route('reservations.index');
}
```

- 引数の型をフォームリクエストにするだけで、**通らなければコントローラに入ってきません**。
  エラーは自動で前の画面に返り、画面側の `errors` に入ります
- 保存に使うのは `$request->validated()` です。`$request->all()` は検証していない値まで通します
- 成功したら `Inertia::flash('toast', ...)` でトーストを出し、`to_route()` でリダイレクトします。
  同じ画面に戻すなら `back()` です
- `type` は `success` / `info` / `warning` / `error`

フォームリクエストの置き場所は、既存の `app/Http/Requests/Settings/` にならって
機能ごとのサブディレクトリでも構いません。

## バリデーション

ルールは配列で書きます。文字列の `'required|email'` も動きますが、
`Rule::` クラスと混ぜるときに配列のほうが読みやすいためです。

**2か所以上で同じルールを書くことになったら**、`app/Concerns/` の trait にまとめます。
既に `PasswordValidationRules` と `ProfileValidationRules` があるので、それにならってください。

```php
// app/Concerns/ReservationValidationRules.php
trait ReservationValidationRules
{
    /**
     * Get the validation rules used to validate reservations.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function reservationRules(): array
    {
        return [ /* ... */ ];
    }
}
```

複数の項目をまたぐ判定（「同じ本をすでに予約していないか」など）は `after()` に書きます。

```php
/**
 * @return array<int, callable>
 */
public function after(): array
{
    return [
        function (Validator $validator) {
            if ($validator->errors()->hasAny(['book_id'])) {
                return;
            }

            $alreadyReserved = $this->user()
                ->reservations()
                ->where('book_id', $this->integer('book_id'))
                ->whereNull('received_at')
                ->exists();

            if ($alreadyReserved) {
                $validator->errors()->add('book_id', __('この資料はすでに予約しています。'));
            }
        },
    ];
}
```

> [!NOTE]
> 検証してから保存するまでの間に、他の人が同じ操作をする可能性は残ります。
> 「1人1冊まで」のように絶対に破られては困る条件は、DB側のユニーク制約も併せて張ってください。

## クエリで気をつけること

### N+1

一覧画面でいちばん踏みやすい問題です。関連を後から参照すると、行数ぶんクエリが飛びます。

```php
// ❌ 蔵書20件で21回クエリが走る
$books = Book::all();
foreach ($books as $book) {
    echo $book->category->category_name;
}

// ✅ 2回で済む
$books = Book::with('category')->get();
```

件数だけ欲しいときは、関連を読み込まずに数えます。

```php
$books = Book::withCount('reservations')->get();
$books->first()->reservations_count;
```

開発中に気付けるようにするなら、`AppServiceProvider::boot()` に1行足すと
未読み込みの関連へアクセスした時点で例外になります。

```php
Model::preventLazyLoading(! app()->isProduction());
```

### 一覧は必ずページネーション

蔵書は件数が読めません。`get()` や `all()` ではなく `paginate()` を使ってください。
一括処理でどうしても全件回すときは `chunkById()` で分割します。

### 日付は CarbonImmutable

`AppServiceProvider` で `Date::use(CarbonImmutable::class)` が設定されています。
**日付は変更されず、新しいインスタンスが返ります。**

```php
$dueDate = $loan->loan_date->addDays(14);   // ✅ 戻り値を使う
$loan->loan_date->addDays(14);              // ❌ 何も起きない
```

## 認可

「ログインしているか」を見るのが認証、「その人がその操作をしてよいか」を見るのが認可です。
`auth` ミドルウェアは前者しか見ません。**他人の予約を開いたり取り消したりできてしまう**のは
ミドルウェアでは防げないので、モデル1件ごとの判定はポリシーに書きます。

```bash
php artisan make:policy ReservationPolicy --model=Reservation
```

```php
// app/Policies/ReservationPolicy.php
namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    /**
     * Determine whether the user can view the reservation.
     */
    public function view(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->user_id;
    }

    /**
     * Determine whether the user can cancel the reservation.
     */
    public function delete(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->user_id
            && $reservation->received_at === null;
    }
}
```

- `App\Models\Reservation` と `App\Policies\ReservationPolicy` は**名前から自動で結び付きます**。
  登録作業は要りません。別名にしたいときだけ、モデルに `#[UsePolicy(SomePolicy::class)]` を付けます
- メソッド名は `viewAny` / `view` / `create` / `update` / `delete` の標準に合わせます
- `viewAny` と `create` は対象の行が無いので、引数は `User` だけです
- 「取り消せるのは受け取り前まで」のような業務的な条件も、ここに一緒に書けます

### コントローラから呼ぶ

> [!IMPORTANT]
> **このプロジェクトの `Controller` 基底クラスは空なので、`$this->authorize()` は使えません。**
> `Gate::authorize()` を使ってください。判定に落ちるとその場で 403 になり、続きは実行されません。

```php
use Illuminate\Support\Facades\Gate;

/**
 * Cancel the reservation.
 */
public function destroy(Reservation $reservation): RedirectResponse
{
    Gate::authorize('delete', $reservation);

    $reservation->delete();

    Inertia::flash('toast', ['type' => 'success', 'message' => __('予約を取り消しました。')]);

    return to_route('reservations.index');
}
```

コントローラで他に何もしないなら、ルートに書くだけでも同じです。
第2引数はルートパラメータの名前（`{reservation}`）を文字列で渡します。

```php
Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy'])
    ->name('reservations.destroy')
    ->can('delete', 'reservation');
```

### 一覧はポリシーではなくクエリで絞る

ポリシーは「この1件を触ってよいか」の判定です。一覧には判定対象の1件が無いので、
**そもそも自分のぶんしか取らない**クエリを書きます。

```php
// ❌ 全件取ってから PHP で弾く（他人のぶんもDBから読んでいる）
$reservations = Reservation::all()->where('user_id', $request->user()->id);

// ✅ 自分のぶんだけ取る
$reservations = $request->user()->reservations()->with('book')->paginate(20);
```

### 画面側での出し分け

ボタンを隠すのは見た目の都合であって、防御ではありません。
**隠したうえで、サーバー側の判定も必ず書きます。** 判定結果は props で渡します。

```php
return Inertia::render('reservations/show', [
    'reservation' => $reservation,
    'canCancel' => $request->user()->can('delete', $reservation),
]);
```

### 認可のテスト

認可は**弾かれること**を確かめないと書いた意味がありません。正常系と対にしてください。

```php
test('users cannot cancel another users reservation', function () {
    $reservation = Reservation::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('reservations.destroy', $reservation))
        ->assertForbidden();
});
```

## 認証まわり

ログイン・登録・パスワードリセット・メール確認は [Fortify](https://laravel.com/docs/fortify) が担当します。
**`routes/web.php` にこれらのルートはありません。** パッケージ側で登録されています。

| やりたいこと                     | 触る場所                                                         |
| -------------------------------- | ---------------------------------------------------------------- |
| ログイン画面の見た目・渡す props | `app/Providers/FortifyServiceProvider.php` の `configureViews()` |
| 登録時のユーザー作成処理         | `app/Actions/Fortify/CreateNewUser.php`                          |
| パスワード再設定の処理           | `app/Actions/Fortify/ResetUserPassword.php`                      |
| 機能のオン・オフ                 | `config/fortify.php` の `features`                               |
| ログイン試行回数の制限           | `FortifyServiceProvider` の `configureRateLimiting()`（5回/分）  |

利用者IDとメールアドレスの紐づけ（職員による初回登録）のように Fortify の標準から外れる処理は、
通常のコントローラとして作り、`CreateNewUser` を参考にしてください。

> [!NOTE]
> パスワードの強度チェックは**本番環境でのみ**有効です（`AppServiceProvider`）。
> ローカルで `password` が通るのは意図的な設定です。

## テスト

Pest を使います。**ほとんどのテストは `tests/Feature/` に置く HTTP テスト**で十分です。

```bash
php artisan make:test ReservationTest --pest
```

```php
// tests/Feature/ReservationTest.php
use App\Models\Book;
use App\Models\Facility;
use App\Models\User;

test('users can reserve a book', function () {
    $user = User::factory()->create();
    $book = Book::factory()->create();
    $facility = Facility::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('reservations.store'), [
            'book_id' => $book->id,
            'receive_facility_id' => $facility->id,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('reservations.index'));

    $this->assertDatabaseHas('reservations', [
        'user_id' => $user->id,
        'book_id' => $book->id,
    ]);
});

test('guests cannot reserve a book', function () {
    $book = Book::factory()->create();

    $this->post(route('reservations.store'), ['book_id' => $book->id])
        ->assertRedirect(route('login'));
});
```

- `tests/Feature/` には `RefreshDatabase` が自動で適用されます（`tests/Pest.php`）。
  各テストの前にDBが巻き戻るので、後片付けは要りません
- URLは直書きせず `route()` を使います。ルートのURLが変わってもテストは通ります
- データはファクトリで作ります。`create()` は保存、`make()` は保存しません
- 正常系だけでなく、**未ログイン・バリデーションエラー・他人のデータ**も確認してください
- テストの説明文は既存に合わせて英語にしています。日本語でも動くので、揃っていれば構いません

```bash
php artisan test --compact                       # 全部
php artisan test --filter=ReservationTest        # 1ファイルだけ
```

ローカルのテストは MySQL の `testing` データベースを使います（`laravel` とは別です）。
CI では SQLite のインメモリで動くため、**MySQL 固有の挙動はローカルでしか再現しません**。

## 書き終えたら

コミット前にコンテナ内で実行します（ホストからは `sail` を頭に付けてください）。

```bash
composer test     # Pint（整形）+ PHPStan + Pest をまとめて
```

個別に動かすなら次のとおりです。

| コマンド               | 中身                                       |
| ---------------------- | ------------------------------------------ |
| `composer lint`        | Pint で整形する（`lint:check` は確認のみ） |
| `composer types:check` | PHPStan / Larastan level 7                 |
| `php artisan test`     | Pest                                       |
| `composer ci:check`    | CI と同じ内容すべて（フロント側も含む）    |

> [!WARNING]
> `composer types:check` は **PHPStan**、`npm run types:check` は **TypeScript** です。
> 名前は同じですが別物なので、PR が落ちたときはどちらが落ちたか確認してください。

PHPStan level 7 はかなり厳しめです。引数と戻り値の型、配列の中身の PHPDoc
（`@return array<string, mixed>`）を省略すると落ちます。既存のコードが全部書いてあるので、
近くのファイルをまねるのが早道です。

## トラブルシューティング

### `SQLSTATE[HY000] [2002] Connection refused`

MySQL がまだ起動中です。`sail ps` で状態を確認して、少し待ってからやり直してください。

### 外部キーでマイグレーションが落ちる（errno 150 / 3780）

だいたい次のどちらかです。

- **親テーブルがまだ無い。** マイグレーションはファイル名の日時順に実行されます。
  `facilities` より先に `book_items` が走ると失敗します。作る順番を意識してください
- **型が違う。** `foreignId()` は `bigint unsigned` です。親側が `$table->id()` でなければ一致しません

### PHPStan が `Access to an undefined property` と言う

モデルの `@property` にそのカラムを書き足してください。
マイグレーションを直したあとは、モデルのコメントも直す必要があります。

### `Route [books.index] not defined`

ルート名の打ち間違いか、`->name()` の付け忘れです。`php artisan route:list --except-vendor` で
実際に登録されている名前を確認できます。

### 画面側で `@/routes/...` が見つからない

ルートを足したあと、フロント側の生成物がまだ作られていません。
`php artisan wayfinder:generate` を実行するか、`npm run dev` を起動し直してもらってください。

### テストだけ落ちる

テストは `testing` データベースを使います。マイグレーションを足した直後で落ちる場合は、
`php artisan test` を実行し直せば `RefreshDatabase` が作り直します。
`php artisan migrate:fresh` は**開発用の `laravel` データベース**を消すので、混同しないでください。

### 日付を変更したのに反映されない

`CarbonImmutable` なので元のインスタンスは変わりません。`$date = $date->addDays(14);` と戻り値を受けてください。
