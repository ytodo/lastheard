/*
 *  Copyright (C) 2019 by Yosh Todo JE3HCZ
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
#define N 256           // 配列標準サイズ
#define LOGMAX 500      // linecount
#define LOGDEL 100      // linecount

/* socket関連*/
unsigned int sock;
struct	sockaddr_in addr;
socklen_t sin_size;
struct	sockaddr_in from_addr;
char	recvbuf[64];    // 受信バッファ

/* 日付表示関連 */
time_t  timer;
struct  tm *timeptr;
char    tmstr[N] = {'\0'};

/* Voice ShortData関連 */
char	sdata[3] = {'\0'};			    // データセグメント
char	sync[] = { 0x55, 0x2d, 0x16 };	// 同期データ 3bytes
char	last[] = { 0x55, 0x55, 0x55 };	// last flame
char	scbl[] = { 0x70, 0x4f, 0x93 };	// scranbleパターン

/* その他 */
FILE    *fp;
char	*LOGFILE = "/var/log/lastheard.log";
char	logline[N] = {'\0'};
char	line[32] = {'\0'};
char	c[1] = {'\0'};
int     i = 0;
int     j = 0;
int     m_flag    = 0;
int     m_sync    = 0;
int     m_counter = 0;

/* linecount関連 */
int     count    = 0;
char    buf[N] = {'\0'};
char    logs[LOGMAX][N];

/* 関数の宣言 */
int header(void);
int slowdata(void);
int write(void);
int linecount(void);

#endif // __RECV_H__
