<?php

$setting = [

    // 送信元の宛名
    'FROM_NAME'  => FROM_NAME,

    // 送信元のメールアドレス(SMTPの設定で上書きされる)
    'FROM_MAIL'  => FROM_MAIL,
    
    // 管理者の宛名
    'ADMIN_NAME' => ADMIN_NAME,

    // 管理者メールアドレス
    'ADMIN_MAIL' => ADMIN_MAIL,

    // WP:Bccで送るメールアドレス array()
    //'ADMIN_MAIL_BCC' => [],

    // ユーザー宛のメールの冒頭文言
    'BODY_BEGINNING' => $beginning,

    // ユーザー宛のメールの署名文言
    'BODY_SIGNATURE' => $signature,

    // 差出人（$Emailのname属性の値）に自動返信メールを送る(送る=1, 送らない=0)
    'RETURN_USER' => 1,

    /*
     * 件名の形式
     * Before_SUBJECT.Subject_ATTRIBUTE.After_SUBJECT
    **/
    // 件名の頭につける文字
    'Before_SUBJECT' => '',

    // 件名の後ろにつける文字
    'After_SUBJECT' => ' - Mailer',

    // 件名にするname属性(name属性に該当なしの場合はNo Subject)
    'Subject_ATTRIBUTE' => '件名',
    
    // Emailのname属性
    'Email_ATTRIBUTE' => 'Email',

    // 必須項目のname属性
    'MANDATORY' => [
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
    'HANKAKU' => ['電話番号','金額'],

    // 送信完了後に表示するページURL
    'END_URL' => '/',

    // 禁止ワードを含む文章をブロック
    // すべての入力フォームが対象のため、例えば @ を禁止にした場合メールアドレスそのものがNGになります。
    // 単語は半角全角を別々の単語と認識します。
    // 半角スペースで1つの単語として区切られます。
    'NG_WORD' => 'bitch fuck',

    // 日本語を含まない文章の受付をブロック
    // 本文にあたるname属性を1つ指定してください。
    'MB_JAPANESE' => 'ご要望',
];
