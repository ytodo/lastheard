***************************************************************************************
   Xchange が搭載されているリピータのラストハードを表示する（C/PHPとD-STARを勉強する）
***************************************************************************************

<h3>Simple Lastheard for D-STAR Repeater Gateway</h3>
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
いてください。
	
PHPの部分は、lastheard.log から index.phpで指定行数を読み込んで、コールサインごと（拡張
子も比較します）に最新の物をWEB表示します。

・httpd を有効にして、ダウンロードしたhtmlフォルダの内容を､そのままの構成で/var/www/html
　に移してください。

・/var/www/html/conf/db.confでリピータの名前（漢字かな使用可）､Last Heard onに続くコール
　サイン（その後ろに続けて文字を入れても読みます）を入力します。　

・また、WEBに表示する行数はデフォルト10行ですが、変更可能です。

・images フォルダーに適当な写真・グラフィックを入れ、その名前を設定すると、指定した位置に
　表示します。

・バックグラウンドカラーを指定すると、WEB の背景色が変わります。

Forum : https://groups.google.com/forum/#!forum/dstarnetwork
