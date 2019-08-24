<?php
/*
 *  Copyright (C) 2018 by Yosh Todo JE3HCZ
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *  本プログラムはD-STAR Network の一部の機能を実現するための物で、
 *  アマチュア無線の技術習得とその本来の用途以外では使用しないでください。
 *
 */

    /* 無線機器のカナ表示を有効にするためシフトJIS に設定 */
    header('Content-type:text/html; charset=Shift_JIS');

    /* 対象のファイルパス */
    $logpath = '/var/log/lastheard.log';
    $cfgpath = './conf/db.conf';
    $multilogpath = '/var/log/multi_forward.log';

    /* 設定ファイルから値を読み込む */
    $fp = fopen($cfgpath, 'r');
    while(!feof($fp)) {
        $line = fgets($fp);
        if (ereg("RPTNAME",  $line)) $rptname  = str_replace("\n", '', substr($line, 8));
        if (ereg("RPTCALL",  $line)) $rptcall  = str_replace("\n", '', substr($line, 8));
        if (ereg("LINES",    $line)) $lines    = str_replace("\n", '', substr($line, 6));
        if (ereg("INTERVAL", $line)) $interval = str_replace("\n", '', substr($line, 9));
        if (ereg("HEAD_PIC", $line)) $head_pic = str_replace("\n", '', substr($line, 9));
        if (ereg("PIC_POSx", $line)) $pic_posx = str_replace("\n", '', substr($line, 9));
        if (ereg("PIC_POSy", $line)) $pic_posy = str_replace("\n", '', substr($line, 9));
        if (ereg("REPEAT",   $line)) $repeat   = str_replace("\n", '', substr($line, 7));
        if (ereg("BGCOLOR",  $line)) $bgcolor  = str_replace("\n", '', substr($line, 8));
    }
    fclose($fp);

    /* WEB を指定秒数でリフレッシュ */
    $sec = intval($interval);

    header("Refresh:$sec; url=index.php");    // index.php
//    header("Refresh:$sec; url=test.php");       // test.php

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="ja">
<head>
    <title>D-STAR DASHBOARD</title>
    <link rel="stylesheet" href="css/db.css">
</head>

<?php
    /* <body> color の設定 */
    $str = sprintf("<body style=\"background-color: %s;\">", $bgcolor);
    echo $str;

    /* <div.wrapper> も同色に設定  == ヘッダー部== */
    $str = sprintf("<div class=\"wrapper\" style=\"background-color: %s;\">", $bgcolor);
    echo $str;

    if ((is_null($head_pic) != true) || (empty($head_pic) != true)) {
        $str = sprintf("<div style=\"background: url('images/%s') %s %s %s;\">", $head_pic, $pic_posx, $pic_posy, $repeat);
        echo $str;
        $flag = 1;
    } else {
        echo '<div>';
    }
    echo '<h1>'.$rptname.'</h1>';
//    if ($flag == 1) echo '<br>';    // 画像を入れた場合スペースを増やす必要の有る時は有効に

    echo '<h2>Connected Remote Users</h2>';
?>


<table> <!-- 接続ユーザリスト--->
    <tr><th style="width:185px;">Time</th>
        <th style="width:95px;">Callsign</th>
        <th style="width:75px;">Port No.</th></tr>

<?php

    /* 配列を宣言 */
    $portcmp = [];

    /* コマンドを実行し標準出力をファイルとしてオープン */
    $fp = popen("tail -n10 ".$multilogpath, 'r');

    /* 全行より接続解除を取得 */
    while($line = fgets($fp)){

        /* 接続解除したポート番号を取得し比較用配列に入れる */
        if (ereg("Disconnect", $line)) {
            $port = str_replace("\n", '', substr($line, strpos($line, '(') + 1, -2));
            $portcmp[] = $port;
        }
    }


    $fp = popen("tail -n10 ".$multilogpath, 'r');

    /* 全行比較し接続を表示 */
    while($line = fgets($fp)){

        /* もし接続ログがあったら */
        if (ereg("Connect from",  $line)) {

            /* ポート番号を取得 */
            $port = str_replace("\n", '', substr($line, strpos($line, '(') + 1, -2));

            /* 接続解除ポートと比較し解除されていなければ */
            if (in_array($port, $portcmp) == false) {



                /* コールサインを取得（空の場合 Unknown とする） */
                if (substr($line, 38, 1) == " ") {
                    $callsign = str_replace("\n", '', substr($line, 39, 8));
                } else {
                    $callsign = "unknown";
                }

                /* 日付/時間を指定の書式に変更 */
                $timestamp = strtotime(str_replace("\n", '', substr($line, 4, 20)));

                /* テーブルの一行を出力 */
                echo "<tr><td>".date('Y/m/d H:i:s', $timestamp)."<td>".$callsign."</td><td align=\"right\">".$port."</td></tr>";
            }
        }

    }
    fclose($fp);

?>
</table>  <!-- 接続ユーザリストEnd--->

<?php
    /* ラストハードタイトルの表示*/
    echo '<h2>Last Heard on '.$rptcall.'</h2>';
?>
</div>

<table> <!-- ラストハードリスト--->
    <tr><th style="width:200px;">Time</th>
        <th style="width:95px;">Callsign</th>
        <th style="width:60px;">Sufix</th>
        <th style="width:45px;">Type</th>
        <th style="width:95px;">UR</th>
        <th style="width:230px;">Message</th>
    </tr>
<?php

    /* ログフィアルからデータを読み取り降順にする */
    $tmp  = file($logpath);
    arsort($tmp);


    /* 読み込みデータの各行を一行ずつ変数に格納し各データに分解 */
    $callcmp = [];     /* 配列 */
    foreach ($tmp as $line) {
        if ($count < $lines) {

            /* 同じコールサイン（拡張子省く）の場合処理をパスする */
            $callsign  = substr($line, 31,  8);
            if (in_array($callsign, $callcmp) == true) continue;

            /* 正常なログ列でなかった場合処理をパスする */
            $timestamp = substr($line,  0, 19);
            if ((substr($timestamp, 0, 4) > 2000) != true) continue;

            /* 過去に出現（表示レベル）していない場合比較配列に入れる */
            $callcmp[] = $callsign;

            /* 他のデータを取得 */
            $suffix    = substr($line, 40,  4);
            $temp      = substr($line, 60,  1);
            if ($temp == 'A') $type = 'ZR';
            if ($temp == 'G') $type = 'GW';
            $ur        = substr($line, 68,  8);
            $message   = substr($line, 90, 20);

            /* もしsuffix欄がnullだったら（Noragateway対策） */
            if ($suffix == " | r") {
                $suffix  = "Null";
                $temp    = substr($line, 56,  1);
                if ($temp == 'A') $type = 'ZR';
                if ($temp == 'G') $type = 'GW';
                $ur      = substr($line, 64,  8);
                $message = substr($line, 86, 20);
            }

            /* 各データをテーブルに表示 */
            if ($timestamp != NULL) {
                echo '<tr>
                      <td>'.$timestamp.'</td>
                      <td>'.$callsign.'</td>';

                /* もしsuffix欄がnullだったら（Noragateway対策） */
                if (substr($suffix, 0, 4) == "Null") {
                    echo '<td style="color:red;">'.$suffix.'</td>';
                } else {
                    echo '<td>'.$suffix.'</td>';
                }
                echo '<td><center>'.$type.'</center></td>
                      <td>'.$ur.'</td>
                      <td>'.$message.'</td>
                      </tr>';
            }
            $count++;
        }
    }

?>
    <tr><td colspan=6 class="footer"></td></tr>
</table> <!-- ラストハードリストend --->

<span class="footer"> <!-- フッター -->
    &nbsp;&nbsp;D-STAR X-change Copyright(c) JARL D-STAR Committee. 'Last Heard' applications are created by Yosh Todo/JE3HCZ CC-BY-NC-SA
</span>

</div>
</body>
</html>
