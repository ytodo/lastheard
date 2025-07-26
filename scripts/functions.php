<?php
/*****************************************************************************
 *  functions.php
 *
 *  変数の定義、関数の定義
 *
 *  Created : 2025.6.25  Last updated : 2025.6.29
 *****************************************************************************/

    // 規定値でタイムゾーンを設定
    date_default_timezone_set('Asia/Tokyo');

    //
    //  変数の定義
    //

    // 対象のファイルパス
    $logpath = '/var/log/lastheard.log';
    $timepath = './rpt/oldesttime.txt';
    $lhuserspath = './rpt/lastheardusers.txt';

    // パスワードフォーム表示初期値
    $show_form = false;

    // リフレッシュコマンド実行指定
    $refresh = true;

    // ブートリンクの表示初期値
    $show_boot = true;

    // このプログラムのファイル名を取得
    $filename = basename(__FILE__);

    // os-releaseを読込みOSを判断
    $fp = popen("cat /etc/os-release", 'r');
    $line = fgets($fp);
    if (preg_match("/Debian/", $line)) $os_name = "Raspbian";
    pclose($fp);

    // OS名とOSごとのログファイルパスを定義
    if ($os_name == "Raspbian")
    {
        $multilogpath = '/var/log/rpi-multi_forward.log';   // Raspberry Pi
    } else {
        $multilogpath = '/var/log/multi_forward.log';       // AlmaLinux
    }


    //
    // 関数の定義
    //

    // .env ローダー
    function load_env($path = '../lastheard/.env')
    {
        // ファイルが無かったら戻る
        if (!file_exists($path)) return;

        // ファイルから行を読む
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line)
        {
            // 読み取った行が無効な行なら読み飛ばす。
            if (strpos(trim($line), '#') === 0 || !strpos($line, '=')) continue;

            // 行を'='の前と後ろに分けて取得
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);

            putenv("$key=$value");
        }
    }


    // 設定ファイルから値を読み込む
    function load_conf($cfgpath = './conf/db.conf')
    {
        $fp = fopen($cfgpath, 'r');
        while(!feof($fp))
        {
            $line = fgets($fp);
            if (preg_match("/RPTNAME/",  $line)) $config['rptname']  = trim(substr($line, 8));		// リピータの名前
            if (preg_match("/RPTCALL/",  $line)) $config['rptcall']  = trim(substr($line, 8));		// リピータのコールサイン
            if (preg_match("/LINES/",    $line)) $config['lines']    = trim(substr($line, 6));		// ラストハードの表示行数
            if (preg_match("/INTERVAL/", $line)) $config['interval'] = trim(substr($line, 9));		// リフレッシュの間隔
            if (preg_match("/HEAD_PIC/", $line)) $config['head_pic'] = trim(substr($line, 9));		// 画像のURL
            if (preg_match("/PIC_POSx/", $line)) $config['pic_posx'] = trim(substr($line, 9));		// 画像表示位置（WEBトップからのドット数
            if (preg_match("/PIC_POSy/", $line)) $config['pic_posy'] = trim(substr($line, 9));		// 　　〃　　　（WEB左からのドット数
            if (preg_match("/REPEAT/",   $line)) $config['repeat']   = trim(substr($line, 7));		// 画像を繰り返し表示するか
            if (preg_match("/BGCOLOR/",  $line)) $config['bgcolor']  = trim(substr($line, 8));		// 背景色
            if (preg_match("/COMMENT/",  $line)) $config['comment']  = trim(substr($line, 8));		// コメント
            if (preg_match("/BR_FLAG/",  $line)) $config['br_flag']  = trim(substr($line, 8));		// 画像がリピータ名に重ならないようにする
        }
        fclose($fp);

        // 配列にして返す。
        return $config;
    }


    // 間隔（秒）を指定してページをリフレッシュさせる関数(既定値5秒）
    function auto_refresh($interval = 5, $refresh)
    {
        // 設定ファイルから読んだ秒数を msec に変換
        $js_interval = intval($interval) * 1000;

        // PHPの条件変数をJavaScriptのboolean型にする
        $js_bool = ($refresh) ? 'true' : 'false';

        // テキストスクリプト
        echo <<<EOT
        <script>
            const interval = $js_interval;
            const shouldRefresh = $js_bool;
            if (shouldRefresh)
            {
                setTimeout(() => {
                    const clearUrl = window.location.origin + window.location.pathname;
                    window.location.href = clearUrl;
                }, interval);
            }
        </script>
        EOT;
    }


	// ログインユーザを取得する関数
	function get_login_user()
	{
		// シェルコマンドの返りを取得
		$p = popen("who", "r");
		$output = fgets($p);
		pclose($p);

		// 空白で分割し、最初の要素(ユーザ名)を得る
		$fields = preg_split('/\s+/', trim($output));
		return $fields[0];
	}
?>
