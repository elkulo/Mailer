<?php

return [

    // (1)送信完了後に戻るページURL
    'RETURN_PAGE' => '/',

    // (2)差出人（指定のEmailのname属性の値）に自動返信メールを送る(送る=1, 送らない=0)
    'IS_REPLY_USERMAIL' => 1,

    // (3)管理者宛のメールで差出人のEmailをReply-Toに含める(含める=1, 含めない=0)
    // ただし、サーバーによってはSMTPのEmailが優先される。
    'IS_FROM_USERMAIL' => 0,

    /**
     * 件名の形式
     * SUBJECT_BEFORE.SUBJECT_ATTRIBUTE.SUBJECT_AFTER
     */
    // (4)件名の頭につける文字
    'SUBJECT_BEFORE' => '',

    // (5)件名の後ろにつける文字
    'SUBJECT_AFTER' => ' - by Mailer',

    // (6)件名にするname属性
    'SUBJECT_ATTRIBUTE' => 'customerTitle',

    // (7)ユーザーのEmailのname属性
    // メールアドレス形式チェックあり
    // (2)が有効の場合の自動返信の送信先
    'EMAIL_ATTRIBUTE' => 'email',

    // (8)name属性とラベルの紐付け（日本語変換の場合）
    // 省略された場合はname属性を出力に使用されます。
    'NAME_FOR_LABELS' => [
        'customerTitle' => '件名',
        'customerType' => '種別',
        'customerName' => 'お名前',
        'customerNameKana' => 'フリガナ',
        'address' => 'ご住所',
        'email' => 'メールアドレス',
        'phoneNumber' => '電話番号',
        'requestContent' => 'ご要望',
        'personalInformation' => '個人情報取扱',
    ],

    // (9)必須項目のname属性
    'REQUIRED_ATTRIBUTE' => [
        'customerTitle',
        'customerType',
        'customerName',
        'customerNameKana',
        'email',
        'requestContent',
        'personalInformation',
    ],

    // (10)全角英数字を半角変換
    // 全角英数字→半角変換を行う項目のname属性の値（name="○○"の「○○」部分）
    // 配列の形「name="○○[]"」の場合には必ず後ろの[]を取ったものを指定して下さい。
    'HANKAKU_ATTRIBUTE' => ['phoneNumber'],

    // (11)日本語を含まない文章の受付をブロック
    // 本文にあたるname属性を1つ指定してください。
    'MB_WORD' => 'requestContent',

    // (12)禁止ワードを含む文章をブロック
    // すべての入力フォームが対象のため、例えば @ を禁止にした場合メールアドレスそのものがNGになります。
    // 単語は半角全角を別々の単語と認識します。
    // 半角スペースで1つの単語として区切られます。
    'NG_WORD' => 'bitch fuck',

    // (13)管理者宛のメールテンプレート(オプション)
    // PHPで生成したTwigテンプレートを使用する場合
    // admin.mail.twig を上書きします。
    'TEMPLATE_MAIL_ADMIN' => '',

    // (14)ユーザ宛のメールテンプレート(オプション)
    // PHPで生成したTwigテンプレートを使用する場合
    // user.mail.twig を上書きします。
    'TEMPLATE_MAIL_USER' => '',
];
