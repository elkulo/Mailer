<?php
/**
 * データベースの設定
 */
return [

  // データベースの種類
  'DB_DRIVER' => env('DB_DRIVER'),

  // データベースのホスト名
  'DB_HOST' => env('DB_HOST'),

  // データベースの名前
  'DB_NAME' => env('DB_NAME'),

  // データベースのユーザー名
  'DB_USER' => env('DB_USER'),

  // データベースのパスワード
  'DB_PASSWORD' => env('DB_PASSWORD'),

  // データベースの接頭辞
  'DB_PREFIX' => env('DB_PREFIX'),

  // データベースの文字セット
  'DB_CHARSET' => env('DB_CHARSET'),

  // データベースの照合順序
  'DB_COLLATE' => env('DB_COLLATE'),

];
