<?php if (! get_class()) {
    exit;
}
header('Content-Type:text/html;charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name='robots' content='noindex,nofollow' />
<title>完了画面</title>
<link rel="stylesheet" type="text/css" media="all" href="template/style.css" />
</head>
<body>
<div id="completion" class="wrapper">
    <div class="page">
        <h1>送信完了</h1>
        <p style="font-size: 100%;">メッセージを送信しました。</p>
        <button class="btn btn-default" onclick="location.href='<?php $this->the_return(); ?>'">ホームページに戻る</button>
    </div>
</div>
</body>
</html>