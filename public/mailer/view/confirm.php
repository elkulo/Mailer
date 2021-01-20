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
<title>確認画面</title>
<link rel="stylesheet" type="text/css" media="all" href="view/style.css" />
</head>
<body>
<div id="confirmation" class="wrapper">
    <div class="page">
        <h1>確認画面</h1>
        <p>以下の内容でお間違いなければ、「送信する」ボタンを押してください。</p>
        <form action="<?php $this->theActionURL(); ?>" method="POST">
            <table>
                <?php $this->theConfirm(); ?>
            </table>
            <?php $this->theCreateNonce(); ?>
            <input type="submit" value="送信する" class="btn btn-lg btn-primary">
            <input type="button" value="キャンセル" class="btn btn-lg btn-default" onClick="history.back()">
        </form>
    </div>
</div>
</body>
</html>