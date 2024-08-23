***************************************************************************************
   Xchange が搭載されているリピータのラストハードを表示する（C/PHPとD-STARを勉強する）
***************************************************************************************

<h3>Simple Lastheard for D-STAR Repeater Gateway (G1)</h3>
Copyright (C) 2019 by Yosh Todo JE3HCZ

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

  本プログラムはD-STAR Network の一部の機能を実現するための物で、
  アマチュア無線の技術習得とその本来の用途以外では使用しないでください。


●構成とインストール

  C言語の部分は、ポートから取り込んだストリームを処理してログファイル/var/log/lastheard.log
  を作成しています。　管理者用ならこのリストを見るだけでも良いと思います。  git clone して
  フォルダー内で make すれば lastheard がコンパイルされます。これを /usr/local/bin 等に置
  き、 lastheard.service を /etc/systemd/system にコピーして自動起動に設定してください。
	
  PHPの部分は、multi_forward.log (Rpiでは rpi-multi_forward.log) から接続状況を取得して、
 ［Remote Users］に表示します。また、lastheard.log から指定行数を読み込んで、コールサインごと
 （拡張子も比較します）に最新の物を表示します。

  ・httpd を有効にして、ダウンロードしたhtmlフォルダの内容を､そのままの構成で/var/www/html
　  に移してください。（Raspberry Piでは PHP をインストールすると自動的に apache2 がセット
　  アップされます。

  ・/var/www/html/conf/db.confでリピータの名前（漢字かな使用可：その場合はShift_JIS保存必要）
　  ､Last Heard 横に続くコメントやリピータ名などを入力します。　

  ・また、WEBに表示する行数はデフォルト10行ですが、変更可能です。

  ・images フォルダーに適当な写真・グラフィックを入れ、その名前を設定すると、指定した位置に
　  表示します。

  ・バックグラウンドカラーを指定すると、WEB の背景色が変わります。

 <s>尚、サーバーのアドレスなど各局の個別のものに関しては、ソースの変更をお願いします。</s>
  <b>・アプリ内でindex.phpはグローバル･アドレスをmonitor.phpはサーバアドレスを取得します。</b>


●注意事項（重要）

  2023年の法令改正に伴う、2024年初等からのゲートウェイ関係アプリケーションの仕様変更に依り
  xchange(rpi-xchange)の起動が、すべてのアプリケーション起動後安定してからになる必要が有る
  ため、タイミングに注意してください。

  lastheard については、xchangeが起動した時点で、サーバが送信する10バイトx2パケットの初期化
  パケットに返答を要する為に、xchangeより先に起動して待ち受けている必要が有ります。

  この初期化パケットについてはD-STAR仕様書7.0?で詳しく説明が加筆されると思いますが、発行まで
  にはもう少しJARL及びメーカー間でのすり合わせが必要で今少し時間を要するものと思われます。

　本プログラムはそのテストの意味も含んで居り、ソースで概要が分かると思いますが、さらに
  *****  変更される可能性もありますので、ご留意ください *****

  なお、他のアプリケーションも同様の初期化パケットによる接続チェックが必要と思われます。

  Forum : https://groups.google.com/forum/#!forum/dstarnetwork
