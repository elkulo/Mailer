<?php
/**
 * バリデーションの設定
 * 
 */
return [

  // Google reCAPTCHAのサイトキー
  'RECAPTCHA_SITEKEY' => env('RECAPTCHA_SITEKEY'),

  // Google reCAPTCHAのシークレットキー
  'RECAPTCHA_SECRETKEY' => env('RECAPTCHA_SECRETKEY'),

  // 不正なメールアドレス形式での投稿時のメッセージ
  'MESSAGE_EMAIL_FORMAT' => 'メールアドレスの形式が正しくありません。',

  // 日本語を含まない投稿時のメッセージ
  'MESSAGE_MULTIBYTE_TEXT' => '日本語を含まない文章は送信できません。',

  // 禁止ワードを含む投稿時のメッセージ
  'MESSAGE_FORBIDDEN_WORD' => '禁止ワードが含まれているため送信できません。',

  // 禁止メールアドレスでの投稿時のメッセージ
  'MESSAGE_FORBIDDEN_EMAIL' => '指定のメールアドレスからの送信はお受けできません。',

  // BOTの疑いがある投稿時のメッセージ
  'MESSAGE_UNKNOWN_ACCESS' => '不明なアクセス',

  // SMTPサーバー障害や設定ミスによる送信失敗時の致命的なエラーメッセージ
  'MESSAGE_FATAL_ERROR' => 'メールの送信でエラーが起きました。別の方法でサイト管理者にお問い合わせください。',

];
