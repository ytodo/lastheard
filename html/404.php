<?php

    // 対象のファイルパス
    $cfgpath = './conf/db.conf';

    // os-releaseを読込みOSを判断
    $fp = popen("cat /etc/os-release", 'r');
    $line = fgets($fp);
    if (preg_match("/Debian/", $line)) $os_name = "Raspbian";
    pclose($fp);

    // 設定ファイルから値を読み込む
    $fp = fopen($cfgpath, 'r');
    while(!feof($fp)) {
        $line = fgets($fp);
        if (preg_match("/RPTNAME/",  $line)) $rptname  = str_replace("\n", '', substr($line, 8));
        if (preg_match("/RPTCALL/",  $line)) $rptcall  = str_replace("\n", '', substr($line, 8));
        if (preg_match("/LINES/",    $line)) $lines    = str_replace("\n", '', substr($line, 6));
        if (preg_match("/INTERVAL/", $line)) $interval = str_replace("\n", '', substr($line, 9));
        if (preg_match("/HEAD_PIC/", $line)) $head_pic = str_replace("\n", '', substr($line, 9));
        if (preg_match("/PIC_POSx/", $line)) $pic_posx = str_replace("\n", '', substr($line, 9));
        if (preg_match("/PIC_POSy/", $line)) $pic_posy = str_replace("\n", '', substr($line, 9));
        if (preg_match("/REPEAT/",   $line)) $repeat   = str_replace("\n", '', substr($line, 7));
        if (preg_match("/BGCOLOR/",  $line)) $bgcolor  = str_replace("\n", '', substr($line, 8));
        if (preg_match("/COMMENT/",  $line)) $comment  = str_replace("\n", '', substr($line, 8));
    }
    fclose($fp);

    // イメージの上下位置が調整できる。（トップからのピクセル値）
    $headerheight="230px";

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift-JIS">

<?php
    echo '<title>'.$rptcall.' D-STAR DASHBOARD</title>';
?>

    <link rel="stylesheet" href="../css/db.css">
</head>

<?php
    // <body> color の設定
    $str = sprintf("<body style=\"background-color: %s;\">", $bgcolor);
    echo $str;

    // <div.wrapper> も同色に設定  == ヘッダー部==
    $str = sprintf("<div class=\"wrapper\" style=\"background-color: %s;\">", $bgcolor);
    echo $str;

    // ヘッダー用の画像が指定されているときの処理
    if ((is_null($head_pic) != true) || (empty($head_pic) != true))
    {
        $str = sprintf("<div style=\"background: url('../images/%s') %s %s %s; height: %s;\">", $head_pic, $pic_posx, $pic_posy, $repeat, $headerheight);
        echo $str;
        $flag = 1;
    }
    else
    {   // 指定されていないとき
        echo '<div>';
    }

    echo '<h1>'.$rptcall.' '.$rptname.'</h1>';
    if ($flag == 1) echo '<br>';    // 画像を入れた場合タイトルと重なるのを防ぐ時は有効に

?>
</div>
<br clear="all">
<h2>この局の信号分析データ並びにグラフは生成されていません。</h2>
</body>
</html>
