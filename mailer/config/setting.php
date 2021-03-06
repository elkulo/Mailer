<?php

return array(

    // 送信完了後に戻るページURL
    'RETURN_PAGE' => '/',

    // 差出人（$Emailのname属性の値）に自動返信メールを送る(送る=1, 送らない=0)
    'IS_REPLY_USERMAIL' => 1,

    // 管理者宛のメールで差出人のEmailをReply-Toに含める(含める=1, 含めない=0)
    // ただし、サーバーによってはSMTPのEmailが優先される。
    'IS_FROM_USERMAIL' => 0,

    /**
     * 件名の形式
     * SUBJECT_BEFORE.SUBJECT_ATTRIBUTE.SUBJECT_AFTER
     */
    // 件名の頭につける文字
    'SUBJECT_BEFORE' => '',

    // 件名の後ろにつける文字
    'SUBJECT_AFTER' => ' - by Mailer',

    // 件名にするname属性(該当なしの場合はNo Subject)
    // TODO: 該当なしは出力しない
    'SUBJECT_ATTRIBUTE' => '件名',

    // ユーザーのEmailのname属性(メールアドレス形式チェックあり)
    'EMAIL_ATTRIBUTE' => 'Email',

    // name属性とラベルの紐付け（日本語変換の場合）
    // 省略された場合はname属性を出力に使用されます。
    // TODO: 未実装
    'NAME_FOR_LABELS' => [
        'name' => 'お名前',
        'nameKana' => 'フリガナ',
        'emailAddress' => 'メールアドレス',
        'phoneNumber' => '電話番号'
    ],

    // 必須項目のname属性
    'REQUIRED_ATTRIBUTE' => [
        'お名前',
        'Email',
        'フリガナ',
        '件名',
        'ご要望',
        '個人情報取扱',
    ],

    // 全角英数字を半角変換
    // 全角英数字→半角変換を行う項目のname属性の値（name="○○"の「○○」部分）
    // 配列の形「name="○○[]"」の場合には必ず後ろの[]を取ったものを指定して下さい。
    'HANKAKU_ATTRIBUTE' => ['電話番号','金額'],

    // 禁止ワードを含む文章をブロック
    // すべての入力フォームが対象のため、例えば @ を禁止にした場合メールアドレスそのものがNGになります。
    // 単語は半角全角を別々の単語と認識します。
    // 半角スペースで1つの単語として区切られます。
    'NG_WORD' => 'bitch fuck',

    // 日本語を含まない文章の受付をブロック
    // 本文にあたるname属性を1つ指定してください。
    'MB_WORD' => 'ご要望',

    // 管理者宛のメールテンプレート(オプション)
    // PHPで生成したTwigテンプレートを使用する場合
    // admin.mail.twig を上書きします。
    'TEMPLATE_MAIL_ADMIN' => '',

    // ユーザ宛のメールテンプレート(オプション)
    // PHPで生成したTwigテンプレートを使用する場合
    // user.mail.twig を上書きします。
    'TEMPLATE_MAIL_USER' => '',
);
