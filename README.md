# Mailer

## Git Clone

~~~
git clone https://github.com/elkulo/Mailer.git Mailer
cd Mailer
npm install
~~~

## Command Line

### Develop
~~~
npm run develop
~~~

### Deploy
~~~
npm run deploy
~~~

## How To Mailer

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
