#!/bin/sh

# mailerディレクトリのZipを作成
zip -r dist/Mailer-latest-ja.zip mailer/ -x '*.git*' '*.env*' '*.DS_Store' '*__MACOSX*'

# publicディレクトリをexample名でコピー
cp -R public/ example/

# exampleディレクトリをZipに追加
zip -r dist/Mailer-latest-ja.zip example/ -x '*.git*' '*.env*' '*.DS_Store' '*__MACOSX*'

# exampleディレクトリを削除
rm -rf example

# 完了
echo "Zip is finished!"
