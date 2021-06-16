# Mailer

開発バージョンでは、意図しない不具合が発生する可能性があります。開発以外では本番バージョンの最新パッケージをご利用ください。

## 設置方法

### Step.1

formタグのaction属性を post.php にする

~~~
<form method="post" action="mailer/post.php">
 :
</form>
~~~

### Step.2

env.sample.txt を .env にリネーム
.envにSMTPの情報を設定

### Step.3

config/setting.php で必須項目等の設定

### プログラムは公開ディレクトリの外に設置を推奨

post.php をrequire_onceで別のPHPファイルで読み込めば、任意のディレクトリで実行できます。
Publicの公開ディレクトリの外（httpでアクセスできない場所）に設置することができます。

#### !!プログラムを公開ディレクトリ内に設置している場合!!

公開ディレクトリにプログラムを設置している場合は、logsディレクトリにアクセス制限をかけましょう。
logsディレクトリはメール送信失敗などのログファイルが残ります。

## 開発環境のクローン

開発環境をクローンしてどなたでも開発に参加いただけます。

~~~
git clone https://github.com/elkulo/Mailer.git Mailer
cd Mailer
npm install
~~~

### コマンドライン

#### 開発

PHPのビルドサーバーとWebpack(非圧縮)の監視サーバーが起動します。

~~~
npm run start
~~~

#### プロダクト

Webpackで圧縮したファイルにビルドします。

~~~
npm run build
~~~
