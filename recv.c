/**************************************************
 *  Dashboard for D-STAR Repeater Gateway         *
 *      recv.h version 00.01                      *
 *      2018.12.17 -                              *
 *                                                *
 *  Xchange が搭載されているリピータの            *
 *  ラストハードを表示する                        *
 *                                                *
 **************************************************/

#include "recv.h"

int	m_flag = 0;

/**************************************************
 * ラストハード･ログ生成メインモジュール          *
 **************************************************/
int main(void)
{
	/* IPv4 UDP のソケットを作成*/
        if((sock = socket(AF_INET, SOCK_DGRAM, 0)) < 0) {
                perror("socket");
                return (-1);
        }

        /* 待ち受けるIP とポート番号を設定 */
        addr.sin_family = AF_INET;
        addr.sin_port = htons(50100);           /* 受信ポートxchage より */
        addr.sin_addr.s_addr = INADDR_ANY;

        /* バインドする */
        if (bind(sock, (struct sockaddr *)&addr, sizeof(addr)) < 0) {
                perror("bind");
                return (-1);
        }

        /* UDP パケットの捕捉と仕分け、再構成 */
        while (1) {

                /* 受信バッファの初期化 */
                memset(recvbuf, 0, sizeof(recvbuf));

                /* 受信パケット用ソケット */
                if (recvfrom(sock, recvbuf, sizeof(recvbuf), 0, (struct sockaddr *)&from_addr, &sin_size) < 0) {
                        perror("recvfrom");
                        return (-1);
                }

                /* 管理データL の値によりタスクを分ける */
                switch (recvbuf[9]) {

		/* 最初のフレーム */
		case 0x30:

			/* access time */
			timer = time(NULL);
			timeptr = localtime(&timer);
			strftime(tmstr, N, "%Y/%m/%d %H:%M:%S", timeptr);

			/* access time によるダブり防止 */
			if (strcmp(tmstr, tmstrpre) == 0) break;
			strcpy(tmstrpre, tmstr);

			/* ヘッダー情報表示サブへ */
			header(recvbuf);
			break;

		/* 第3 フレーム以降 */
		case 0x13:

			/* Slow Data 表示サブルーティン */
 			slowdata(recvbuf);
			break;

		/* ラストフレーム */
		case 0x16:

			/* Last flame */
			m_counter = 0;
			break;

		default:

			break;
                }
        }

        /* ソケットのクローズ   */
        close(sock);
}


/**************************************************
 * 関数の定義                                     *
 **************************************************/


/**************************************************
 * インターネット側通信ヘッダ（status)の表示      *
 *                                                *
 * 音声系データの管理データL （L の後ろに続く     *
 * データ長）から判断   0x30 （４８バイト）。     *
 * インターネット側はスクランブルされない。       *
 **************************************************/
int header(char *recvbuf)
{

	/* access time  */
	printf("%s", tmstr);

	/* My   */
	printf(" D-STAR my: ");
	for (j = 0; j < 8; ++j)	printf("%c", recvbuf[44 + j]);
	printf("/");
	for (j = 0; j < 4; ++j) printf("%c", recvbuf[52 + j]);
	printf(" |");

	/* rpt1 */
	printf(" rpt1: ");
	for (j = 0; j < 8; ++j) printf("%c", recvbuf[28 + j]);
	printf(" |");

	/* ur   */
	printf(" ur: ");
	for (j = 0; j < 8; ++j) printf("%c", recvbuf[36 + j]);
	printf(" |");

	return (0);
}


/**************************************************
 * データセグメント（Slow Data ）情報の構成と表示 *
 *                                                *
 * データセグメント３バイト（24bits）を取り出して *
 * sync フレームとlast フレームを判別。           *
 * さらにそれ以外の２フレームを再構成し６バイト   *
 * （48bits ）のデータを作成するときスクランブルを*
 * 解除する。                                     *
 * そのデータの最初の１バイト（ミニヘッダ8bits　）*
 * に規定されている内容によってメッセージを再構成 *
 * する。   メッセージ用ミニヘッダ（0x40～0x43)   *
 **************************************************/
int slowdata(char *recvbuf)
{
	/* データセグメントの3バイトを取得 */
	for (i = 0; i < 3; i++) {
		sdata[i] = recvbuf[26 + i];
	}

	/* sync packetか? */
	if (memcmp(sdata, sync, 3) == 0) {
		memset(sdata, 0, sizeof(sdata));
		return;
	}

	/* Last Flameか？ */
	if (memcmp(sdata, last, 3) == 0) {
		printf("\n");
		m_counter = 0;
		m_flag = 0;
		return;
	}

	/* データ・セグメントのスクランブルを解く */
        for (i = 0; i < 3; i++) {
		sdata[i] = (sdata[i] ^ scbl[i]);
	}

	/* ミニヘッダ 0x40 ～0x43 のメッセージを選別 */
	if ((sdata[0] >= 0x40) && (sdata[0] <= 0x43) && (m_flag == 0)) {

		/* メッセージ20 バイトの先頭にタイトル */
		if (sdata[0] == 0x40) printf(" Short MSG: ");

	        /* ミニヘッダ以外の２バイト（16bits）を表示 */
		printf("%c%c", sdata[1], sdata[2]);
		m_counter++;
		m_flag = 1;
		return;
	}

	/* ミニヘッダを含まないブロック３バイト（24bits ）を接続 */
	if (m_flag == 1) {
		printf("%c%c%c", sdata[0], sdata[1], sdata[2]);
		m_counter++;
		m_flag = 0;
	}

	/* メッセージの末尾又は合成パケット四つ分(カウント８）でクリア */
	if (m_counter > 7) {
		m_flag = 2;
	}

	return (0);
}
