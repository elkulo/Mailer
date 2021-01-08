<?php if (! get_class()) {
    exit;
}
header('Content-Type:text/html;charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name='robots' content='noindex,nofollow' />
<title>入力エラー</title>
<link rel="stylesheet" type="text/css" media="all" href="templates/style.css" />
</head>
<body>
<div id="error" class="wrapper">
    <div class="page">
        <h1><strong>入力エラー</strong>: 下記をご確認の上「前画面に戻る」ボタンにて修正をお願い致します。</h1>
        <?php echo $this->error_massage; ?>
        <input type="button" value="前画面に戻る" class="btn btn-lg btn-danger" onClick="history.back()">
    </div>
</div>
</body>
</html>