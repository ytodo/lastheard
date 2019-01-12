/**************************************************
 *  Dashboard for D-STAR Repeater Gateway         *
 *      lastheard version 1.0                     *
 *      2018.12.17 - 2019.01                      *
 *                                                *
 *  Xchange が搭載されているリピータの            *
 *  ラストハードを表示する                        *
 *                                                *
 *                    Created by Yosh Todo/JE3HCZ *
 **************************************************/

#include "recv.h"

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
    addr.sin_port = htons(52000);           /* 受信ポートxchage より */
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
    sprintf(logline, "%s", tmstr);

    /* My   */
    strcat(logline, " D-STAR my: ");
    line[0] = '\0';
    for (j = 0; j < 8; ++j) {
        sprintf(c, "%c", recvbuf[44 + j]);
        strcat(line, c);
    }
    strcat(logline, line);
    strcat(logline, "/");
    line[0] = '\0';
    for (j = 0; j < 4; ++j) {
        sprintf(c, "%c", recvbuf[52 + j]);
        if (c == '\0' || c == "") strcpy(c, " ");
        strcat(line, c);
    }
    strcat(logline, line);
    strcat(logline, " |");

    /* rpt1 */
    strcat(logline, " rpt1: ");
    line[0] = '\0';
    for (j = 0; j < 8; ++j) {
        sprintf(c, "%c", recvbuf[28 + j]);
        strcat(line, c);
    }
    strcat(logline, line);

    /* ur   */
    strcat(logline, " | ur: ");
    line[0] = '\0';
    for (j = 0; j < 8; ++j) {
        sprintf(c, "%c", recvbuf[36 + j]);
        strcat(line, c);
    }
    strcat(logline, line);
    strcat(logline, " |");

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
        write(logline);
        linecount();
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
        if (sdata[0] == 0x40) strcat(logline, " Short MSG: ");

            /* ミニヘッダ以外の２バイト（16bits）を表示 */
        line[0] = '\0';
        sprintf(line, "%c%c", sdata[1], sdata[2]);
        strcat(logline, line);
        m_counter++;
        m_flag = 1;
        return;
    }

    /* ミニヘッダを含まないブロック３バイト（24bits ）を接続 */
    if (m_flag == 1) {
        line[0] = '\0';
        sprintf(line, "%c%c%c", sdata[0], sdata[1], sdata[2]);
        strcat(logline, line);
        m_counter++;
        m_flag = 0;
    }

    /* メッセージの末尾又は合成パケット四つ分(カウント８）でクリア */
    if (m_counter > 7) {
        m_flag = 2;
    }

    return (0);
}


/**************************************************
 * ログの一行分をログファイルに出力               *
 *                                                *
 * LOGFILE で示すファイルパスに追加で書き込む     *
 **************************************************/
int write(char *logline)
{
    FILE    *fp;

    /* ログファイルを追加モードでオープンする */
    if ((fp = fopen(LOGFILE, "a")) == NULL) {
        printf("File open error!\n");
        return (-1);
    }

    /* ログ一行を書き込む */
    fprintf(fp, "%s\n", logline);

    /* ファイルを閉じる */
    fclose(fp);

    return (0);
}



/**************************************************
 * ログが規定行数を超えたら古いものから削除       *
 *                                                *
 * LOGFILE で示すファイルパスから配列に読み込み   *
 * 規定を越えた行数分を古いエントリーから省いて   *
 * 再度上書きする。                               *
 **************************************************/
int linecount(void)
{
    FILE    *fp;
    char    buf[N];
    char    log[N][128];
    int     count = 0;
    int countMAX = 1000;
    int countDEL = 200;

    /* ログファイルを読み取りでオープン */
    if ((fp = fopen(LOGFILE, "r")) == NULL) {
        printf("File open error!\n");
        return (-1);
    }

    /* 各行を2次元配列に入れる */
    while (fgets(buf, sizeof(buf), fp) != NULL) {
        sprintf(&log[count][0], "%s", buf);
        count++;
    }

    /* ファイルを閉じる */
    fclose(fp);

    /* 規定行数を越えたら古い方から指定行数省いて書き込み */
    if (count > countMAX) {

        /* ログファイルを上書きでオープン */
        if ((fp = fopen(LOGFILE, "w")) == NULL) {
            printf("File open error!\n");
            return (-1);
        }

        /* */
        for (i = countDEL; i < count; i++) {
            fprintf(fp, "%s", &log[i][0]);
        }
    }

    /* ファイルを閉じる */
    fclose(fp);

    return 0;
}
