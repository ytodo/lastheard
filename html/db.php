<?php
    /* 無線機器のカナ表示を有効にするためシフトJIS に設定 */
    header('Content-type:text/html; charset=Shift_JIS');

    /* 対象のファイルパス */
    $logpath = '/var/log/lastheard.log';
    $cfgpath = './conf/db.conf';

    /* 設定ファイルから値を読み込む */
    $fp = fopen($cfgpath, 'r');
    while(!feof($fp)) {
        $line = fgets($fp);
        if (ereg("RPTNAME",  $line)) $rptname  = substr($line, 8);
        if (ereg("RPTCALL",  $line)) $rptcall  = substr($line, 8);
        if (ereg("LINES",    $line)) $lines    = substr($line, 6);
        if (ereg("INTERVAL", $line)) $interval = substr($line, 9);
    }
    fclose($fp);

    /* WEB を指定秒数でリフレッシュ */
    $sec = intval($interval);
    header("Refresh:$sec; url=db.php");

    /* ログフィアルからデータを読み取り降順にする */
    $tmp  = file($logpath);
    arsort($tmp);
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="ja">
<head>
    <title>D-STAR DASHBOARD</title>
    <link rel="stylesheet" href="css/db.css">
</head>
<body>
<div class="wrapper"> <!-- 中央表示用ラップ -->

<?php /* WEB ヘッダー */
    echo '<h1>'.$rptname.'</h1>';
    echo '<h2>Last Heard on '.$rptcall.'</h2>';
?>

<table> <!-- ラストハードリスト--->
    <tr><th>Time</th><th>Callsign</th><th>Sufix</th><th>Type</th><th>UR</th><th>Message</th></tr>
<?php
    /* 読み込みデータの各行を一行ずつ変数に格納し各データに分解 */
    $callcmp = [];     /* 配列 */
    foreach ($tmp as $line) {
        if ($count < $lines) {

            /* 同じコールサイン（拡張子省く）の場合処理をパスする */
            $callsign  = substr($line, 31,  8);
            if (in_array($callsign, $callcmp) == true) continue;

            /* 過去に出現（表示レベル）していない場合比較配列に入れる */
            $callcmp[] = $callsign;

            /* 新たに拡張子も含むコールサインを含めデータ取得 */
            $timestamp = substr($line,  0, 19);
            $suffix    = substr($line, 40,  4);
            $temp      = substr($line, 60,  1);
            if ($temp == 'A') $type = 'ZR';
            if ($temp == 'G') $type = 'GW';
            $ur        = substr($line, 68,  8);
            $message   = substr($line, 90, 20);

            /* 各データをテーブルに表示 */
            if ($timestamp != NULL) {
                echo '<tr>
                      <td>'.$timestamp.'</td>
                      <td>'.$callsign.'</td>
                      <td>'.$suffix.'</td>
                      <td><center>'.$type.'</center></td>
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
