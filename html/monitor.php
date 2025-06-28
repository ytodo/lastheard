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

    $version = "v.2.2.1";

    // functionの定義
    require_once '../lastheard/functions.php';

//==========================================================
//  環境設定
//==========================================================

    // 環境設定の読み込み
    load_env();

    // 複数の変数を配列に読み込む
    $config = load_conf();

	// /proc/uptime から起動後の時間を読む
    if ($os_name == "Raspbian")
	{
		$uptime = (float) shell_exec("awk '{print $1}' /proc/uptime");		// Alma用のコードでRasPiもOKなら削除
	} else {
		$uptime_data = file_get_contents("/proc/uptime");
		$uptime_parts = explode(" ", $uptime_data);
		$uptime = floatval($uptime_parts[0]);
	}

    // ブート・リブート後60秒以内だったらリフレッシュを強制する
    if ($uptime < 60)
    {
        // リプレッシュを必要に応じて指定間隔で実行
        auto_refresh($config['interval'], true);
    }
?>

<!--
===========================================================
    表示用環境設定（db_jp.confからの設定を反映）$config
===========================================================
-->

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/db.css">

    <?php
	    echo '<title>'.$config['rptcall'].' D-STAR DASHBOARD</title>';
    ?>
</head>

<?php
	// <body> color の設定
	$str = sprintf("<body style=\"background-color: %s;\">", $config['bgcolor']);
	echo $str;

	// <div.wrapper> も同色に設定  == ヘッダー部==
	$str = sprintf("<div class=\"wrapper\" style=\"background-color: %s;\">", $config['bgcolor']);
	echo $str;

	// ヘッダー用の画像が指定されているときの処理
	if ((is_null($config['head_pic']) != true) || (empty($config['head_pic']) != true))
	{
		$str = sprintf("<div style=\"background: url('images/%s') %s %s %s;\">", $config['head_pic'], $config['pic_posx'], $config['pic_posy'], $config['repeat']);
		echo $str;
		//$flag = 1;
	}
	else
	{	// 指定されていないとき
		echo '<div>';
	}

	echo '<h1 style="margin-top:1em;margin-bottom:-7px;line-height:1.1em;">'.$config['rptcall'].' '.$config['rptname'].'</h1>';

    $uptime_pretty = shell_exec('uptime -p');
    echo '&nbsp;'.trim($uptime_pretty);

	if ($config['br_flag'] == "enable") echo '<br clear="all">';


//==========================================================
//  接続ユーザの表示
//==========================================================
	echo '<h2>Remote Users</h2>';
?>

<table> <!-- 接続ユーザリスト--->
	<tr><th style="width:185px;">Time</th>
	<th style="width:95px;">Callsign</th>
	<th style="width:75px;">Port No.</th></tr>

<?php

	// Pythonスクリプトを実行しその出力をファイルとして読み取る(rpi-multi_forward Status WEB)
	$command = "python ../lastheard/get_html.py";
	$handle = popen($command, 'r');
	$counter = 0;		// 必要な行を判別するためのカウンタ(必要行 100の台)

	if ($handle)
	{
		// 出力を行ごとに読み取る
		while (($line = fgets($handle)) !== false)
		{
			// 各行を$lineとして処理
			$line = trim($line);  // 不要な空白を削除

			// $lineの内接続クライアントの行を特定する
			if (preg_match("/Client Information/", $line)) $counter = 100;

			// Clientを含む行から2行下の行が接続ユーザデータ
			if ($counter > 102)
			{
				$parts = [];
				$parts = explode("</td><td>", $line);		// tableの一行を各データに分割

				$callsign =  substr($parts[0], 8, 8);		// 最初のpartsの<tr><td>:8文字の後ろ8文字がコールサイン
				$port =  substr($parts[3], 8, -9);			// partsの4つ目がポート <center>xxxx</center>
				$port_preg = "/" . $port . "/";				// preg_matchに使用するため正規表現として'/'で囲う

				// port番号でmulti_forward.logからそのポートが使用された時刻を取得
				$fp = fopen($multilogpath, 'r');

				// すべての接続ログに関してチェック
				while($line2 = fgets($fp))
				{
					// 接続ログフィルタ
					if (preg_match("/Connect from/", $line2))
					{
						// ポート番号が一致したとき
						if (preg_match($port_preg, $line2))
						{
							// ログの先頭にある日付時刻を取得しループを終了
							$logtime = substr($line2, 0, 24);
							$timestamp = strtotime($logtime);

							// ループを出る
							break;
						}

					} else {

						//接続ログでなければ次のループへ
						continue;
					}
				}

				// ログファイルをクローズする
				fclose($fp);

				// 日付/時間、コールサイン、ポートを配列に格納
				$conuser [] = [$timestamp, $callsign, $port];

			}
			$counter++;
			if (preg_match("/ Copyright/", $line)) $counter = 0;
		}

		pclose($handle);
	}

	//----------------------------------------------------------
	//  現在接続中のユーザリストを出力
	//----------------------------------------------------------

	// 配列 $conuser を一行ずつ出力 (直近エントリーを上に）
	arsort($conuser);
	foreach ($conuser as $i => $v)
	{
		// ポート番号が入っていないものは省く
		if ($v[2] !== "")
		{
			echo "<tr><td>".date('Y/m/d H:i:s', $v[0])."<td>".$v[1]."</td><td align=\"right\" width=\"100\">".$v[2]."</td></tr>";
		}
	}

	// 表の下部太罫線
	echo '<tr><td colspan=3 class="footer"></td></tr>';

?>

</table>  <!-- 接続ユーザリストEnd--->

<!--
<b><span style="color:yellow;">"Multi Forward" is out of service >>></span> <a href="https://blog.goo.ne.jp/jarl_lab2" target="_blank" style="color:yellow;text-decoration:none;" >D-STAR NEWS</a></b>
-->

</div>

<?php

//===========================================================
//  ラストハードの表示
//===========================================================

	echo '<h2>Last Heard '.$config['comment'].'</h2>';
?>

<table> <!-- ラストハードリスト--->
	<tr>
		<th style="width:215px;">Time</th>
		<th style="width:100px;">Callsign</th>
		<th style="width:60px;">Sufix</th>
		<th style="width:45px;">Type</th>
		<th style="width:100px;">UR</th>
		<th style="width:225px;">Message</th>
	</tr>

<?php

	// ログフィアルからデータを読み取り降順にする
	$tmp  = file($logpath);
	arsort($tmp);

	// 読み込みデータの各行を一行ずつ変数に格納し各データに分解
	$callcmp = [];		// 配列の宣言
	$count = 0;
	foreach ($tmp as $line)
	{
		if ($count < $config['lines'])
		{
			// 同じコールサイン（拡張子省く）の場合処理をパスする
			$callsign  = substr($line, 31,  8);
			if (in_array($callsign, $callcmp) == true) continue;

			// 正常なログ列でなかった場合処理をパスする
			$timestamp = substr($line,  0, 19);
			if ((substr($timestamp, 0, 4) > 2000) != true) continue;

			// 過去に出現（表示レベル）していない場合比較配列に入れる
			$callcmp[] = $callsign;

			// 他のデータを取得
			$suffix    = substr($line, 40,  4);
			$temp      = substr($line, 60,  1);
			if ($temp == 'A') $type = 'ZR';
			if ($temp == 'G') $type = 'GW';
			$ur        = substr($line, 68,  8);
			$message_sjis   = substr($line, 90, 20);

			// もしsuffix欄がnullだったら（Noragateway対策）
			if ($suffix == NULL)
			{
				$suffix  = "Null";
				$temp    = substr($line, 56,  1);
				if ($temp == 'A') $type = 'ZR';
				if ($temp == 'G') $type = 'GW';
				$ur      = substr($line, 64,  8);
				$message_sjis = substr($line, 86, 20);
			}

            // メッセージのEncodeをUTF-8に変換する(php-mbstringのインストールが必要）
            $message = mb_convert_encoding($message_sjis, 'UTF-8', 'SJIS');

			// 各データをテーブルに表示
			if ($timestamp != NULL)
			{
				echo '<tr><td>'.$timestamp.'</td>';

				// R
				if ($os_name == "Raspbian")
				{
					// $callsignに空白が含まれる場合 $callsign_link にアンダースコアと置き換えたものを入れる
					$callsign_link = str_replace(" ", "_", trim($callsign));

					echo '<td><a href="#" style="text-decoration:none;" onclick="openFixedSizeWindow(\''.trim($callsign_link).'\')">'.htmlspecialchars($callsign).'</a>
					    <script>
                            function openFixedSizeWindow(callsign_link) {
                                window.open("./rpt/" + callsign_link + ".html", "", "width=1020, height=700");
                            }
                        </script>
                        </td>';

					//
					// rpi-monitorのユーザログで使用するため ====================
					//

					// LastHeardの中で最も古い日付を記録
					$fp = fopen($timepath, 'w');
					if ($fp)
					{
						fwrite($fp, $timestamp);
					}
					fclose($fp);

					// 現在LastHeardにリストされているユーザのコールサインを記録
					if ($count == 0)
					{
						$fp = fopen($lhuserspath, 'w');
					}
					else
					{
						$fp = fopen($lhuserspath, 'a');
					}

					if ($fp)
					{
						fwrite($fp, $callsign . PHP_EOL);
					}
					fclose($fp);
					//===========================================================
				}
				else
				{
					echo '<td>'.$callsign.'</td>';
				}

				// もしsuffix欄がnullだったら（Noragateway対策）
				if (substr($suffix, 0, 4) == "Null")
				{
					echo '<td style="color:red;">'.$suffix.'</td>';
				}
				else
				{
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


<div class="footer">
<center>

	<!-- フッター この部分はCC-BY-NC-SAに準じて消さないでください。------------------------------------------------------->
	<span class="footer">D-STAR X-change Copyright(c) JARL D-STAR Committee. <br>
		<b>LastHeard <?php echo $version ?></b> applications are created by Yosh Todo/JE3HCZ <b>CC-BY-NC-SA</b></span>
	<!-- ここまで Creative Commons BY-NC-SA ------------------------------------------------------------------------------>
	<br><br>


	<!-- このメッセージ欄は適宜変更してお使いください。上下のコメントタグを削除すると有効になります。 -------------------->
	<!--
    <span style="color:#ffffff;font-size:16pt;"><b>D-STAR GATEWAY on Raspberry Pi OS Bookworm 64bit</b></span><BR>
    <span style="color:#ffffff;font-size:16pt;"><b>and Echo Server is available on JL3ZBS Z</b></span><br>
	<span style="color:#333399;font-size:16pt;"><b>Last Heard is also available on AlmaLinux</b></span>
    <br><br>
	-->

	<!-- アプリケーションのバージョン情報を表示し、個別の管理WEBへのリンクを設定 -->

	<span style="color:white;font-size:16pt;">アプリケーションのバージョン情報</span><br>
	<hr size="0" width="50%" color="#333399">

	<?php
	// os-releaseを読込みOSを判断
	//$fp = popen("cat /etc/os-release", 'r');
	//$line = fgets($fp);
	//if (preg_match("/Debian/", $line)) $os_name = "Raspbian";
	//pclose($fp);

	// このサーバのグローバルIPアドレスを取得
	if ($filename == "index.php")
	{
		$server_ip = file_get_contents('https://api.ipify.org');
	} else {
		$server_ip = $_SERVER['SERVER_ADDR'];
	}

	// PiOSの場合
	if ($os_name == "Raspbian")
	{
		// xchange のバージョン情報を取得
		$fp = popen("apt-cache madison rpi-xchange", 'r');
		$line = fgets($fp);
		$xchange_ver = str_replace("\n", '', substr($line, 19, 5));
		pclose($fp);

		/* multi_forward のバージョン情報を取得 */
		$fp = popen("apt-cache madison rpi-multi-forward", 'r');
		$line = fgets($fp);
		$multi_ver = str_replace("\n", '', substr($line, 25, 5));
		pclose($fp);

		/* dsgwd のバージョン情報を取得 */
		$fp = popen("apt-cache madison rpi-dsgwd", 'r');
		$line = fgets($fp);
		$dsgwd_ver = str_replace("\n", '', substr($line, 18, 5));
		pclose($fp);

		/* dstatus のバージョン情報を取得 */
		$fp = popen("apt-cache madison rpi-dstatus", 'r');
		$line = fgets($fp);
		$dstatus_ver = str_replace("\n", '', substr($line, 19, 5));
		pclose($fp);

		/* decho のバージョン除法を取得 */
		$fp = popen("apt-cache madison rpi-decho", 'r');
		$line = fgets($fp);
		$echo_ver = str_replace("\n", '', substr($line, 18, 5));
		pclose($fp);

		/* d-prs のバージョン除法を取得 */
		$fp = popen("apt-cache madison rpi-dprs", 'r');
		$line = fgets($fp);
		$dprs_ver = str_replace("\n", '', substr($line, 18, 5));
		pclose($fp);

		/* rpi-monitor のバージョン情報を取得 */
		$fp = popen("apt-cache madison rpi-monitor", 'r');
		$line = fgets($fp);
		$monitor_ver = str_replace("\n", '', substr($line, 19, 5));
		pclose($fp);

		// バージョン情報を表示
		echo '<a style="font-size:12pt; color:white;" href="http://'.$server_ip.':20200" target="_blank">'."rpi-dsgwd v.".$dsgwd_ver.'</a><br>';
		echo '<a style="font-size:12pt; color:white;" href="http://'.$server_ip.':20201" target="_blank">'."rpi-xchange v.".$xchange_ver.'</a><br>';
		echo '<a style="font-size:12pt; color:white;" href="http://'.$server_ip.':20202" target="_blank">'."rpi-multi_forward v.".$multi_ver.'</a><br>';
		echo '<a style="font-size:12pt; color:white;" href="http://'.$server_ip.':20203" target="_blank">'."rpi-dprs v.".$dprs_ver.'</a></br>';
		echo '<a style="font-size:12pt; color:white;" href="http://'.$server_ip.':20204" target="_blank">'."rpi-dstatus v.".$dstatus_ver.'</a><br>';
		echo '<a style="font-size:12pt; color:white;" href="http://'.$server_ip.':20205" target="_blank">'."rpi-decho v.".$echo_ver.'</a><br>';
		echo '<a style="font-size:12pt; color:white;">'."rpi-monitor v.".$monitor_ver.'</a>';
	}
	else
	{	// AlmaLinux又はCentOSの場合

		// dsgwd のバージョン情報を取得
		$fp = popen("rpm -qa | grep dsgwd", 'r');
		$line = fgets($fp);
		$dsgwd_ver = str_replace("\n",'', substr($line, 9, 6));
		pclose($fp);

		// xchange のバージョン情報を取得
		$fp = popen("rpm -q  xchange", 'r');
		$line = fgets($fp);
		$xchange_ver = str_replace("\n", '', substr($line,8 , 7));
		pclose($fp);

		// multi_forward のバージョン情報を取得
		$fp = popen("rpm -q multi_forward", 'r');
		$line = fgets($fp);
		$multi_ver = str_replace("\n", '', substr($line, 14, 7));
		pclose($fp);

		// dprs のバージョン情報を取得
		$fp = popen("rpm -q dprs", 'r');
		$line = fgets($fp);
		$dprs_ver = str_replace("\n", '', substr($line, 5, 7));
		pclose($fp);

		// dstatus のバージョン情報を取得
		$fp = popen("rpm -q dstatus", 'r');
		$line = fgets($fp);
		$dstatus_ver = str_replace("\n", '', substr($line, 8, 7));
		pclose($fp);

		// decho のバージョン情報を取得
		$fp = popen("rpm -q decho", 'r');
		$line = fgets($fp);
		$decho_ver = str_replace("\n", '', substr($line, 6, 7));
		pclose($fp);

		// バージョン情報を表示
		echo '<span style="font-size:12pt; color:white;">'."dsgwd v.".$dsgwd_ver.'</span><br>';
		echo '<a style="font-size:12pt; color:white;" href="http://'.$server_ip.':8080" target="_blank">'."xchange v.".$xchange_ver.'</a><br>';
		echo '<a style="font-size:12pt; color:white;" href="http://'.$server_ip.':8081" target="_blank">'."multi_forward v.".$multi_ver.'</a><br>';
		echo '<a style="font-size:12pt; color:white;" href="http://'.$server_ip.':8082" target="_blank">'."dprs v.".$dprs_ver.'</a></br>';
    	echo '<a style="font-size:12pt; color:white;" href="http://'.$server_ip.':8083" target="_blank">'."dstatus v.".$dstatus_ver.'</a><br>';
		echo '<a style="font-size:12pt; color:white;" href="http://'.$server_ip.':8084" target="_blank">'."decho v.".$decho_ver.'</a>';
	}

	?>

	<hr size="0" width="50%" color="#333399">
	<br>

    <!-- CPU の温度表示 -->

	<div style="background-color:white;">
	<?php   // Get temperature

		if ($os_name == "Raspbian")
		{
			$fp = popen("cat /sys/class/thermal/thermal_zone0/temp", 'r');
			$temp = fgets($fp);
			$temp = round($temp * 0.001,1);
			if (strlen($temp) < 3) $temp = $temp.".0";
			pclose($fp);
		}
		else    // AlmaLinux  要インストール lm-sensors / sensors-detectを一度実行（すべてデフォルトでEnter）
		{
			$temp = shell_exec("sensors | awk '/Package id 0:/ { gsub(/\\+|°C/, \"\", \$4); printf \"%.1f\\n\", \$4 }'");
		}

		// 温度により色を変えて表示
		echo '<span style="font-size:12pt;">Server Temp.: ';

        if ($temp < 45) {
            echo '<span style="color:white;background-color:green;">'.$temp.'℃</span>';
        } elseif ($temp < 50) {
            echo '<span style="color:black;background-color:yellow;">'.$temp.'℃</span>';
        } elseif ($temp < 55) {
             echo '<span style="color:black;background-color:orange;">'.$temp.'℃</span>';
        } else {
            echo '<span style="color:yellow; background-color:red;">'.$temp.'℃</span>';
        }
	?>
	</div>
    <br>

	<?php
    //
    // パスワード認証によるダッシュボードからのサーバ再起動
    //

	// action=reboot が付いているとき
    if (isset($_GET['action']) && $_GET['action'] === 'reboot')
    {
        // リンクからの遷移ならフォームを表示しリフレッシュを止める
        $show_form = true;
        $refresh = false;
    }

    // パスワード認証処理
    if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        $password = $_POST['password'] ?? '';

		// 入力パスワードが別途保存したパスワードと合致したとき
        if ($password === trim(getenv('AUTH_PASSWORD')))
        {
            // MQTTコマンドなどを実行
            exec("sleep 5 && mosquitto_pub -h localhost -t 'raspi/control/reboot' -m 'REBOOT' > /dev/null 2>&1 &");
            echo "<span style='color:white;font-size:12pt;'>✅ 認証成功：再起動コマンドを送信しました。</span>";

            //フォームとREBOOTリンクを消し、自動リフレッシュ
            $show_form = false;
            $refresh = true;
            $show_boot = false;
        }
        else
        {
            // 認証失敗
            echo "<span style='color:white;font-size:12pt;'>❌ パスワードが間違っています。</span>";

            // フォームを表示したまま、メッセージのみ表示（REBOOTは消す）し、リフレッシュも止めたまま
            $show_form = true;
            $refresh = false;
            $show_boot = false;
        }

    }

	?>

	<!-- REBOOT リンク -->
	<?php if ($show_boot): ?>
    	<a href="?action=reboot"><span style='font-size:12pt;color:white;'>REBOOT</span></a>
	<?php endif; ?>

	<!-- もしパスワード入力フォームが表示されていたら -->
	<?php if ($show_form): ?>
    <form method="POST" style="margin-top: 10px; font-size:12pt;">
        <input type="password" name="password" placeholder="Input password." required autofocus>
        <input type="submit" value="Send">
    </form>
	<?php endif; ?>
</center>

<?php
	// リプレッシュを必要に応じて指定間隔で実行
    auto_refresh($config['interval'], $refresh);
?>


</div>
</div>
</body>
</html>

