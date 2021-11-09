# Mailer for Github

**Githubで公開しているコードは開発中のバージョンです。**

開発バージョンでは、テスト中のコードも含むため意図しない不具合が発生する可能性があります。

本番環境では最新パッケージをパブリッシャーのサイトから[ダウンロード](https://walkyxwalky.com/download/mailer)してご利用ください。

## 特徴

Slim Framework 4 製のADR（Action-Domain-Responder）パターンを取り入れたモダンでシンプルなメールフォーム（以下「本プログラム」という）です。

HTMLファイルからの送信、または内部Twigテンプレートからの送信の両側面からのPOSTをサポートしています。

そのため、WordPressなどのCMSや、Laravelといったフレームワークなど既存システムに組み込むことができます。

提供する機能はメールの送信プロセスに特化しているため、パスワードを必要とする管理画面を持ちません。
それはセキュリティ面でのリスク回避と、他CMSやフレームワークとの連携を容易にします。

**PHPバージョンは2021年11月現在 v7.4 および v8.0 をサポートします。**

## 機能

- HTMLファイル等からのPOSTに対応（CSRF対策のAjaxライブラリ同梱）
- CSRF対策（slim/csrf対応）
- BOT対策（reCAPTCHAv3対応）
- 指定ドメインのみのPOST受付によるスパム対策
- ワンタイムトークンによる連続投稿を防止
- 基本的なバリデーション機能（valitron,email-validator対応）
- 数字の全角を半角に自動変換
- 自動応答メールの送信
- 確認画面の有無
- 日本語含まない投稿をブロック
- 禁止ワードの設定によるブロック
- 指定メールアドレスまたはドメインからの投稿をブロック
- HTML形式の応答メールに対応
- 自由なHTML/CSSでフォームをデザイン（Twigテンプレート対応）
- 自由な応答メールをデザイン（Twigテンプレート,PHPテンプレート対応）
- 他CMSやフレームワークとの共存が可能
- ヘルスチェック機能
- ディレクトリの自由配置（env,settings,templates,public）
- メールハンドラーの切り替え（PHPMailer,wp_mail関数）
- 履歴のデータベース保存（MariaDB,MySQL,SQLite）
- Logger搭載（Monolog）
- JavaScriptのAjaxライブラリ同梱（CSRFトークン,reCAPTCHAトークン,Bootstrap v5）
- マルチフォーム対応
- マルチサイト対応
- 多言語でのフォーム使い分けに対応

---

### マルチフォーム＆マルチサイトの設置

環境設定(.env),設定(settingsディレクトリ),テンプレート(templateディレクトリ),公開ディレクトリ(publicディレクトリ)のパスを自由に変えることができます。

そのため、一つの本プログラムで設定もデザインもまったく違うメールフォームを複数設置することができます。

また、同一のサーバー内であれば、一つの本プログラムでドメインを跨いだ複数サイトのメールフォームを機能させることも可能です。

---

### 多言語でのフォーム使い分け

環境設定(.env),設定(settingsディレクトリ),テンプレート(templateディレクトリ),公開ディレクトリ(publicディレクトリ)のパスを自由に変えることができるということは、一つの本プログラムで言語毎のフォームを使い分けて設置することが可能です。

---

### ヘルスチェックの実行

環境設定変更後はヘルスチェック機能を実行することをオススメします。ヘルスチェックによってSMTP等の設定に問題がないか確認することができます。また、データベースのセットアップも同時に行います。

ヘルスチェックは本プログラムのルートURLの後ろに /health-check でアクセスできます。また、ヘルスチェックはデバッグモードがオフでも利用できます。

~~~
https://www.example.com/your/public/path/health-check
~~~

---

### 履歴のデータベース保存

データベースの利用は任意です。必要がなければデータベースの設定はスキップできます。

送信履歴をデータベースに保存することができます。本プログラムがサポートするのは **保存のみ** です。もし、Web上での管理が必要であれば他のツールと連携して行ってください。
データベースを利用するには環境設定にデータベース情報を記載後、必ずヘルスチェックを実行する必要があります。

---

### 将来実装予定

- 添付ファイルの送信に対応予定
- CRONで定期レポートの送信に対応予定

---

## 設置方法

### Step.1

publicフォルダは任意の場所に移動ができます。
移動させたら本プログラムのルートにある bootstrap.php までのパスを指定してください。

~~~
require_once __DIR__ . '/../../mailer/bootstrap.php';
~~~

### Step.2

環境設定 env.sample.txt を .env にリネームしてSMTP情報等を編集します。.envの環境設定は settings ディレクトリ内の各PHPファイルを通り再設定されます。
そのため、他のプログラムと連携する場合は settings ディレクトリ内の各PHPファイルで設定値を上書きすると良いでしょう。

### Step.3

環境設定のDEBUGをtrueにしてデバッグモードで publicフォルダのルートにアクセスしてください。
セットアップ方法が表示されますので、ガイドに沿ってフォームの内容をカスタマイズしてみましょう

---

### プログラムは公開ディレクトリの外に設置を推奨

本プログラムのルートにある bootstrap.php を起点にすべてのプログラムを読み込みます。

試しに本プログラム直下のpublicフォルダを任意の場所に移動してみましょう。

移動させたら publicフォルダ内の index.php と htaccess.txt を編集してください。
フォルダ名も public から自由に変更してください。

**本プログラムの core ディレクトリは公開ディレクトリの外（HTTPでアクセスできない場所）に設置することを推奨します。**

### !!プログラムを公開ディレクトリ内に設置している場合!!

公開ディレクトリにプログラムを設置している場合は、coreディレクトリ にアクセス制限をかけましょう。
coreディレクトリ の中の logs にはメール送信失敗などのログファイルが残ります。
また、履歴の保存にSQLiteを使用する場合は databaseディレクトリ 本プログラム直下に作成されるので、SQLiteファイルの場所にも気をつけてください。

---

[Mailer](https://github.com/elkulo/Mailer/)  
Copyright 2020-2021 A.Sudo  
Licensed under LGPL-2.1-only  
https://github.com/elkulo/Mailer/blob/main/LICENSE
