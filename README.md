# 図書館予約管理システム

## リソース
- Figma: https://www.figma.com/design/ajdUuKqg5j7bABAvxTmQIs/%E5%9B%B3%E6%9B%B8%E9%A4%A8%E4%BA%88%E7%B4%84%E3%82%B7%E3%82%B9%E3%83%86%E3%83%A0?node-id=0-1&t=3prdRg3gRul8KdH1-1
- Notion: https://app.notion.com/p/38e006b1b9d48001af13d33960d2b322

## 開発

### 前提条件

- Docker Desktop または Orbstack

### コンテナと開発サーバーの起動と停止

```sh
# バックエンドサーバー起動
./vendor/bin/sail up

# フロントエンドサーバー起動
./vendor/bin/sail bun dev
```

どちらも Ctrl+C で停止できます。

## チームルール

- すべての変更は、mainブランチに直接コミット/プッシュせず、プルリクエスト（以下「PR」）を介して行う。
- PRは、発行者以外の1人の承認の後マージできる。マージ作業は発行者自身で行うこと。
- mainブランチへの強制pushは絶対に行わない。
