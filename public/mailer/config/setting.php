<?php

$setting = array(

    // 送信元の宛名
    'FROM_NAME'  => FROM_NAME,

    // 送信元のメールアドレス(SMTPの設定で上書きされる)
    'FROM_MAIL'  => FROM_MAIL,
    
    // 管理者の宛名
    'ADMIN_NAME' => ADMIN_NAME,

    // 管理者メールアドレス
    'ADMIN_MAIL' => ADMIN_MAIL,

    // WP:Bccで送るメールアドレス array()
    'ADMIN_BCC' => [],

    // ユーザー宛のメールの冒頭文言(削除予定)
    'BODY_BEGINNING' => <<< EOM
{お名前}様

お問い合わせありがとうございました。

内容を確認後、担当の者からご連絡いたします。
送信内容は以下になります。
EOM,

    // ユーザー宛のメールの署名文言(削除予定)
    'BODY_SIGNATURE' => <<< EOM
Mailerから送信
EOM,

    // 差出人（$Emailのname属性の値）に自動返信メールを送る(送る=1, 送らない=0)
    'RETURN_USER' => 1,

    /*
     * 件名の形式
     * SUBJECT_BEFORE.SUBJECT_ATTRIBUTE.SUBJECT_AFTER
    **/
    // 件名の頭につける文字
    'SUBJECT_BEFORE' => '',

    // 件名の後ろにつける文字
    'SUBJECT_AFTER' => ' - Mailer',

    // 件名にするname属性(name属性に該当なしの場合はNo Subject)
    'SUBJECT_ATTRIBUTE' => '件名',
    
    // Emailのname属性
    'EMAIL_ATTRIBUTE' => 'Email',

    // 必須項目のname属性
    'MANDATORY_ATTRIBUTE' => [
        'お名前',
        'Email',
        'フリガナ',
        '件名',
        'ご要望',
        '個人情報取扱',
    ],

    // {名前}のname属性
    // フォーム側の「名前」箇所のname属性の値　※自動返信メールで置き換えが必要な場合
    'DISPLAY_NAME' => 'お名前',
    
    // 管理者宛のメールで差出人を送信者のメールアドレスにする(する=1, しない=0)
    // SMTPのメールアドレスが優先される。
    'IS_FROM_USERMAIL' => 0,

    // 全角英数字を半角変換
    // 全角英数字→半角変換を行う項目のname属性の値（name="○○"の「○○」部分）
    // 配列の形「name="○○[]"」の場合には必ず後ろの[]を取ったものを指定して下さい。
    'HANKAKU_ATTRIBUTE' => ['電話番号','金額'],

    // 送信完了後に戻るページURL
    'END_URL' => '/',

    // 禁止ワードを含む文章をブロック
    // すべての入力フォームが対象のため、例えば @ を禁止にした場合メールアドレスそのものがNGになります。
    // 単語は半角全角を別々の単語と認識します。
    // 半角スペースで1つの単語として区切られます。
    'NG_WORD' => 'bitch fuck',

    // 日本語を含まない文章の受付をブロック
    // 本文にあたるname属性を1つ指定してください。
    'MB_WORD' => 'ご要望',

    // メールテンプレート
    'MAIL_TEMPLATE_ADMIN' => file_get_contents(__DIR__.'/mail/send-to-admin.tpl'),
    'MAIL_TEMPLATE_USER' => file_get_contents(__DIR__.'/mail/send-to-user.tpl'),
);
