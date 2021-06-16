#!/bin/sh

# mailerディレクトリのZipを作成
zip -r dist/Mailer-latest-ja.zip mailer/ -x '*.git*' '*.env*' '*.DS_Store' '*__MACOSX*' '*.log*'

# publicディレクトリをexample名でコピー
cp -R public/ example/

# exampleディレクトリをZipに追加
zip -r dist/Mailer-latest-ja.zip example/ -x '*.git*' '*.env*' '*.DS_Store' '*__MACOSX*'

# exampleディレクトリを削除
rm -rf example

# LICENSEをLICENSE.txtにしてZipに追加
cp -i LICENSE LICENSE.txt
zip -j dist/Mailer-latest-ja.zip LICENSE.txt
rm -f LICENSE.txt

# README.mdをZipに追加
zip -j dist/Mailer-latest-ja.zip README.md

# 完了
echo "Zip is finished!"
