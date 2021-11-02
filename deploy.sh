#!/bin/sh

# mailerディレクトリのZipを作成
zip -r dist/Mailer-latest-ja.zip mailer/ -x '*.git*' '*.env*' '*.DS_Store' '*__MACOSX*' '*.log*' '*/logs/' '*.sql*' '*/database/'

# publicディレクトリをexampleに変更
cp -R public/ example/

# htaccessをhtaccess.txtに変更
mv -n example/mailer-alias/.htaccess example/mailer-alias/htaccess.txt

# exampleをZipに追加
zip -r dist/Mailer-latest-ja.zip example/ -x '*.git*' '*.env*' '*.DS_Store' '*__MACOSX*'

# exampleを削除
rm -rf example

# LICENSEをLICENSE.txtにしてZipに追加
cp -i LICENSE LICENSE.txt
zip -j dist/Mailer-latest-ja.zip LICENSE.txt
rm -f LICENSE.txt

# README.mdをZipに追加
zip -j dist/Mailer-latest-ja.zip README.md

# 完了
echo "Zip is finished!"
