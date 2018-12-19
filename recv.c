#include "recv.h"

int main(void)
{
	/* IPv4 UDP のソケットを作成*/
        if((sock = socket(AF_INET, SOCK_DGRAM, 0)) < 0) {
                perror("socket");
                return (-1);
        }

        /* 待ち受けるIP とポート番号を設定 */
        addr.sin_family = AF_INET;
        addr.sin_port = htons(50100);           /* 受信ポートxchageより */
        addr.sin_addr.s_addr = INADDR_ANY;

        /* バインドする */
        if (bind(sock, (struct sockaddr *)&addr, sizeof(addr)) < 0) {
                perror("bind");
                return (-1);
        }

        /* UDPパケットの捕捉と仕分け、再構成 */
        while (1) {

                /* 受信バッファの初期化 */
                memset(recvbuf, 0, sizeof(recvbuf));

                /* 受信　パケットが到着するまでブロック */
                if (recvfrom(sock, recvbuf, sizeof(recvbuf), 0, (struct sockaddr *)&from_addr, &sin_size) < 0) {
                        perror("recvfrom");
                        return (-1);
                }

                /* 管理データL の値によりタスクを分ける */
                switch (recvbuf[9]) {

                        /* 最初のフレーム */
                        case 0x30:

                                /*
                                 * インターネット側通信ヘッダ（status)の表示
                                 *
                                 * 音声系データの管理データL （L の後ろに続くデータ長）から判断
                                 * 0x30 （４８バイト）。インターネット側はスクランブルされない。
				*/

                                /* access time  */
                                timer = time(NULL);
                                timeptr = localtime(&timer);
                                strftime(tmstr, N, "%Y/%m/%d %H:%M:%S", timeptr);
				if (strcmp(tmstr, tmstrpre) == 0) break;
				strcpy(tmstrpre, tmstr);
                                printf("%s", tmstr);

                                /* My   */
                                printf(" D-STAR my: ");
                                for (j = 0; j < 8; ++j)
                                        printf("%c", recvbuf[44 + j]);
                                printf("/");
                                for (j = 0; j < 4; ++j)
                                        printf("%c", recvbuf[52 + j]);
                                printf(" |");

                                /* rpt1 */
                                printf(" rpt1: ");
                                for (j = 0; j < 8; ++j)
                                        printf("%c", recvbuf[28 + j]);
                                printf(" |");

                                /* ur   */
                                printf(" ur: ");
                                for (j = 0; j < 8; ++j)
                                        printf("%c", recvbuf[36 + j]);
                                printf(" |");

                                break;

                        /* 第3 フレーム以降 */
                        case 0x13:

                                /*
                                 * データセグメント
                                 *
                                 * データセグメント３バイト（24bits）を取り出して, sync フレームと
                                 * last フレームを判別。
				 * さらにそれ以外の２フレームを再構成し６バイト（48bits ）のデータ
				 * を作成するときスクランブルを解除する。
                                 * そのデータの最初の１バイト（8bits　）をミニヘッダと称し、規定さ
                                 * れている内容によってメッセージを再構成する。
                                 *
                                 * メッセージ用ミニヘッダ（0x40～0x43)
                                 */

                                /* データセグメントの3バイトを取得 */
                                for (i = 0; i < 3; i++) {
                                        sdata[i] = recvbuf[26 + i];
                                }

                                /* sync packetか? */
                                if ((memcmp(sdata, sync, 3) == 0) && m_counter == 0) {
                                        memset(sdata, 0, sizeof(sdata));
                                        break;
                                }

                                /* Last Flameか？ */
                                if (memcmp(sdata, last, 3) == 0) {
                                        printf("\n");
					m_counter = 0;
                                        break;
                                }

                                /* データ・セグメントのスクランブルを解く */
                                for (i = 0; i < 3; i++) {
                                        sdata[i] = (sdata[i] ^ scbl[i]);
                                }

                                /* ミニヘッダ 0x40 ～0x43 のメッセージを選別 */
                                if ((sdata[0] >= 0x40) && (sdata[0] <= 0x43) && (m_counter % 2 == 0)) {

					/* メッセージ20 バイトの先頭にタイトル */
					if (sdata[0] == 0x40) printf(" Short MSG: ");

                        	        /* ミニヘッダ以外の２バイト（16bits）を表示 */
                                	printf("%c%c", sdata[1], sdata[2]);
                                        if (sdata[0] == 0x43) m_EOF++;
					m_counter++;
					break;
        	                }

	                	/* ミニヘッダを含まないブロック３バイト（24bits ）を接続 */
        	                if (m_counter % 2 == 1) {
        	      	                printf("%c%c%c", sdata[0], sdata[1], sdata[2]);
					m_counter++;
	                                if (m_EOF >= 1) {
                	                       	m_EOF = 0;
						m_counter = 0;
	                                }
//	       	                        if (m_counter > 20) {
//                	               	        printf("\n");
//                        	               	m_counter = 0;
//	                                }
					break;
				}

                        /* ラストフレーム       */
                        case 0x16:
                                /* Last flame   */
                                m_counter = 0;
                                break;

                        default:

                                break;
                }
        }

      printf("</body></html>\n");

        /* ソケットのクローズ   */
        close(sock);
}

