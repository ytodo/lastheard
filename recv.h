/**************************************************
 *  Dashboard for D-STAR Repeater Gateway         *
 *      recv.h version 00.01                      *
 *      2018.12.17 -                              *
 *                                                *
 *  Xchange が搭載されているリピータの            *
 *  ラストハードを表示する                        *
 *                                                *
 **************************************************/
#ifndef __RECV_H__
#define __RECV_H__

/* header files */
#include <stdio.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <memory.h>
#include <time.h>
#include <string.h>

/* macros */
#define N 256

/* socket関連*/
unsigned int sock;
struct	sockaddr_in addr;
socklen_t sin_size;
struct	sockaddr_in from_addr;
char	recvbuf[64];		/* 受信バッファ */

/* 日付表示関連 */
time_t  timer;
struct  tm *timeptr;
char    tmstr[N] = {'\0'};
char    tmstrpre[N] = {'\0'};		/* 時刻の同じ二重パケットを排除するため使用 */

/* Voice ShortData関連 */
char   sdata[3];			/* データセグメント */
char   sync[] = { 0x55, 0x2d, 0x16 };	/* 同期データ 3bytes */
char   last[] = { 0x55, 0x55, 0x55 };	/* last flame */
char   scbl[] = { 0x70, 0x4f, 0x93 };	/* scranbleパターン */
char   mesg[32];

/* その他 */
char	c;
int     i = 0;
int     j = 0;
int     m_counter = 0;
int     m_EOF = 0;

/* 関数の宣言 */
int header(char *recvbuf);
int slowdata(char *recvbuf);

#endif // __RECV_H__
