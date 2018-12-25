<?php
    header('Content-type:text/html; charset=UTF-8');

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
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <link rel="stylesheet" href="css/db.css">
</head>
<body>
<div class="wrapper">
<?php
    echo '<h1>'.$rptname.'</h1>';
    echo '<h2>'.$rptcall.' Last Heard</h2>';
?>
<table>
    <tr><th>Time</th><th>Callsign</th><th>Sufix</th><th>Type</th><th>UR</th><th>Message</th></tr>
<?php
    /* 読み込みデータの各行を一行ずつ変数に格納し各データに分解 */
    foreach ($tmp as $line) {
        if ($count < $lines) {
            $timestamp = substr($line,  0, 19);
            $callsign  = substr($line, 31,  8);
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
        }
        $count++;
    }
?>
</table>
</div>
</body>
</html>
