//////////////////////////////////////////////////////
//
//  Dashboard for D-STAR Repeater Gateway(RpiGW)
//      lastheard version 2.0.0
//      2018.12.17 - 2024.01.17
//
//  rpi-xchange が搭載されているリピータの
//  ラストハードを表示する
//
//                    Created by Yosh Todo/JE3HCZ
//
//////////////////////////////////////////////////////

#include "recv.h"

//////////////////////////////////////////////////////
//
//	ラストハード･ログ生成メインモジュール
//
//////////////////////////////////////////////////////

int main()
{
	int count	= 0;		// 初期パケットの処理の2回だけをカウントする
	int i		= 0;

	// IPv4 UDP のソケットを作成
	if((sock = socket(AF_INET, SOCK_DGRAM, 0)) == -1)
	{
		perror("socket");
		return -1;
	}

	// 待ち受けるIP とポート番号を設定
    addr.sin_family = AF_INET;
    addr.sin_port = htons(52000);           // 受信ポートxchageにて設定

	// inet_ptonを使用してIPアドレスをバイナリ形式に変換
	if (inet_pton(AF_INET, "127.0.0.1", &(addr.sin_addr)) <= 0)
	{
		perror("inet_pton");
		return 1;
	}

	// バインドする
	if (bind(sock, (struct sockaddr *)&addr, sizeof(addr)) == -1)
	{
		perror("bind");
		return -1;
	}

	// UDPパケットの捕捉と仕分け、再構成 */
	while (1)
	{

		// 受信バッファの初期化
		memset(recvbuf, 0, sizeof(recvbuf));

		// 受信パケット用ソケット
		if (recvfrom(sock, recvbuf, sizeof(recvbuf), 0, (struct sockaddr *)&from_addr, &from_len) == -1)
		{
			perror("receive");
			return -1;
		}


		/*
		 *	初期化パケット: 7バイト目recvbuf[6]が 's'であった場合 'r'に変更して返信する
		 */

		// 7バイト目が0x73(文字s)の場合
		if (recvbuf[6] == 0x73) {

			count++;

			/*// 受信データをプリント DEBUG用 ----------------------
			for (i = 0;i < 10; i++) {
				printf("受信データ: %c\n", recvbuf[i]);
			} ----------------------------------------------------*/

			// 送信バッファに、recvbufを10バイト分代入する
			for (i = 0; i < 10; i++) {
				sendbuf[i] = recvbuf[i];
			}

			// 7バイト目sendbuf[6]を'r'に変更
			sendbuf[6] = 0x72;

			// 2回目以降のヘッダーに'DSTR'が含まれる場合、さらに8，9，10バイト目を'0x00'にする。
 			if (strncmp(recvbuf, "DSTR", 4))
			{
				sendbuf[7] = 0x00;
				sendbuf[8] = 0x00;
				sendbuf[9] = 0x00;
			}

			// 初期化パケットを返す(recvfromのアドレスに送信=sendtoを使用）
			if (sendto(sock, sendbuf, 10, 0, (struct sockaddr *)&from_addr, from_len) == -1)
			{
				perror("sendto");
				return(-1);
			}

			/*// 送信データをプリント　DEBUG用 ----------------------
			for (i = 0;i < 10; i++)
			{
				printf("Sent Data: %c\n", sendbuf[i]);
			} -----------------------------------------------------*/

			//　初期化パケットの時はすぐ繰り返す。INITとDSTRと言う初期化専用パケットは10バイトで速い処理が必要
			if (count < 2)	continue;
		}


		/*
		 *	音声系データの管理データL （L の後ろに続くデータ長）から判断
		 *	0x30 （４８バイト）, 0x13 （19バイト）、0x16 （22バイト）等
		 */

		switch (recvbuf[9])
		{
		// ヘッダー48バイトの処理
		case 0x30:

			// 同一通話ID （PTTON からPTTOFF まで）で一度ログを出す(書き出し直後にflag を３にセットして判断)
			if (m_flag != 3)
			{
				// ヘッダーをログ分として構成する
				header();
			}

			break;

		// ヘッダー部に続く19バイトのフレーム
		case 0x13:

			// 各フレーム末尾３バイト（24bits ）のデータセグメントを処理する
			for (i = 0; i < 3; i++)
			{
				sdata[i] = recvbuf[26 + i];
			}

			// Last Flameか？
			if (memcmp(sdata, lastflm, 3) == 0)
			{
				linecount();
				m_counter = 0;
				m_flag = 0;
				m_sync = 0;

				break;
			}

			 // sync packetか?
			if (memcmp(sdata, syncflm, 3) == 0)
			{
				memset(sdata, 0, sizeof(sdata));
				m_sync = 1;

				break;
			}

			// 同一通話ID （PTTON からPTTOFF まで）で一度ログを出す(書き出し直後にflag を３にセットして判断)
			if ((m_flag < 2) && (m_sync == 1))
			{
				// Slow Data 表示関数
				slowdata();
			}

			// slowdata の構成が終了した時点でm_flag に２がセットされる
			if (m_flag == 2)
			{
				// 一行ログを出力する
				writelog();

				// その時点でm_flag を３にセットしてLast Flame を待つ
				m_flag = 3;
				m_sync = 0;
			}

			break;

		// ラストフレーム
		case 0x16:

			// Last flame
			m_counter = 0;
			m_flag = 0;
			m_sync = 0;

			break;

		default:

		break;
		}
	}

	// ソケットのクローズ
	close(sock);

	return 0;
}


//////////////////////////////////////////////////////
//
//	関数の定義
//
//////////////////////////////////////////////////////

//////////////////////////////////////////////////////
//
//	インターネット側通信ヘッダ（status)の表示
//
//////////////////////////////////////////////////////

/*
 *	音声系データの管理データL（L の後ろに続く
 *	データ長）から判断   0x30 （４８バイト）。
 *	インターネット側はスクランブルされない。
 */

int header(void)
{

	// access time
	timer = time(NULL);
	timeptr = localtime(&timer);
	strftime(tmstr, N, "%Y/%m/%d %H:%M:%S", timeptr);
	sprintf(logline, "%s", tmstr);

	// My
	strcat(logline, " D-STAR my: ");
	line[0] = '\0';
	for (j = 0; j < 8; ++j)
	{
		sprintf(c, "%c", recvbuf[44 + j]);
		strcat(line, c);
	}
	strcat(logline, line);
	strcat(logline, "/");
	line[0] = '\0';
	for (j = 0; j < 4; ++j)
	{
		sprintf(c, "%c", recvbuf[52 + j]);
		if (c == '\0' ) strcpy(c, " ");
		strcat(line, c);
	}
	strcat(logline, line);
	strcat(logline, " |");

	// rpt1
	strcat(logline, " rpt1: ");
	line[0] = '\0';
	for (j = 0; j < 8; ++j) {
		sprintf(c, "%c", recvbuf[28 + j]);
		strcat(line, c);
	}
	strcat(logline, line);

	// ur
	strcat(logline, " | ur: ");
	line[0] = '\0';
	for (j = 0; j < 8; ++j)
	{
		sprintf(c, "%c", recvbuf[36 + j]);
		strcat(line, c);
	}
	strcat(logline, line);
	strcat(logline, " |");

	return(0);
}


//////////////////////////////////////////////////////
//
//	データセグメント（Slow Data ）情報の構成と表示
//
//////////////////////////////////////////////////////

/*
 *	データセグメント３バイト（24bits）を取り出して
 *	sync フレームとlast フレームを判別。
 *	さらにそれ以外の２フレームを再構成し６バイト
 *	（48bits ）のデータを作成するときスクランブルを
 *	解除する。
 *	そのデータの最初の１バイト（ミニヘッダ8bits)
 *	に規定されている内容によってメッセージを再構成
 *	する。   メッセージ用ミニヘッダ（0x40～0x43)
 */

int slowdata(void)
{
	// データ・セグメントのスクランブルを解く
	for (i = 0; i < 3; i++) {
		sdata[i] = (sdata[i] ^ scblflm[i]);
	}

	// ミニヘッダ 0x40 ～0x43 のメッセージを選別
	if ((sdata[0] >= 0x40) && (sdata[0] <= 0x43) && (m_flag == 0))
	{
		// メッセージ20 バイトの先頭にタイトル
		if (sdata[0] == 0x40) strcat(logline, " Short MSG: ");

		// ミニヘッダ以外の２バイト（16bits）を表示
		line[0] = '\0';
		sprintf(line, "%c%c", sdata[1], sdata[2]);
		strcat(logline, line);
		m_counter++;
		m_flag = 1;

		return 0;
	}

	// ミニヘッダを含まないブロック３バイト（24bits ）を接続
	if (m_flag == 1)
	{
		line[0] = '\0';
		sprintf(line, "%c%c%c", sdata[0], sdata[1], sdata[2]);
		strcat(logline, line);
		m_counter++;
		m_flag = 0;
	}

	// メッセージの末尾又は合成パケット四つ分(カウント８）でクリア
	if (m_counter > 7)
	{
        // ログ一行の書き出しフラッグを立てる
		m_flag = 2;
	}

	return 0;
}


//////////////////////////////////////////////////////
//
//	ログの一行分をログファイルに追加出力
//
//////////////////////////////////////////////////////

int writelog(void)
{
	// ログファイルを追加モードでオープンする
	if ((fp = fopen(LOGFILE, "a")) == NULL)
	{
		printf("File open error!\n");
		return (-1);
	}

	// ログ一行を書き込む
	fprintf(fp, "%s\n", logline);
	printf("%s\n", logline);

	// ファイルを閉じる
	fclose(fp);

	return (0);
}


//////////////////////////////////////////////////////
//
//	ログが規定行数を超えたら古いものから削除
//
//////////////////////////////////////////////////////

/*
 * LOGFILE で示すファイルパスから配列に読み込み
 * 規定を越えた行数分を古いエントリーから省いて
 * 再度上書きする。
 */

int linecount()
{
	int		count		= 0;
	int		i			= 0;
	char	buf[N]		= {'\0'};
	char	logs[LOGMAX + 1000][N]   = {'\0'};


	// ログファイルを読み取りでオープン
	if ((fp = fopen(LOGFILE, "r")) == NULL)
	{
		printf("Log file open error! (Read)\n");
		return (-1);
	}

	// 各行を2次元配列に入れる
	while ((fgets(buf, sizeof(buf), fp)) != NULL)
	{
		sprintf(&logs[count][0], "%s", buf);
		count++;
	}

	// ファイルを閉じる
	fclose(fp);

	// 規定行数を越えたら古い方から指定行数省いて書き込み
	if (count > LOGMAX)
	{
		// ログファイルを上書きでオープン
		if ((fp = fopen(LOGFILE, "w")) == NULL) {
			printf("Log file open error! (Write)\n");
			return (-1);
		}

		// 配列入れたデータを削除件数省いて書き出す
		for (i = 0; i < (count - LOGDEL); i++)
		{
			fprintf(fp, "%s", &logs[i + LOGDEL][0]);
		}

    	// ファイルを閉じる
	    fclose(fp);
	}

	return (0);
}
