#########################################################################
#                                                                       #
#   rpi-monitor.logを整理して各ユーザごとの情報データベースを作成       #
#                                                                       #
app_name = "log2database"                                               #
app_ver  = "0.0.2"                                                      #
#                                                                       #
#                  Copyright (C) 2025  Created by Y.Todo / JE3HCZ       #
#########################################################################

from pathlib import Path
import datetime
import time
import re
import os
import logging

# 追加されたログのみを位置変数を利用して抽出する
def read_new_lines(logfile, last_position, stop_keyword="ホールパンチをONにしてみて下さい", interval=5):

    logging.info("読み取り開始")

    # 変数の定義
    new_lines = []
    empty_lines = 0                                                 # 空白行のカウント
    exit_loop = False

    # １ブロック読み取る間のループ
    while True and not exit_loop:

        with open(logfile, 'r') as f:
            f.seek(last_position)                                   # 前回の読み取り位置に移動
            lines = f.readlines()
            new_position = f.tell()                                 # 現在の読み取り位置を記録

        # linesにデータが存在する場合
        if lines:

            # すべての行について処理する
            for line in lines:

                # 空白の行数をカウントする
                if line.strip() == "":
                    empty_lines += 1                                # 空白行ならカウント
                else:
                    empty_lines = 0                                 # 何かデータが来たらリセット

                # すべての行を追加
                new_lines.append(line)

                #
                # データブロック終端処理
                #

                # 特定のキーワードを含む行があったらループを抜ける(最終行と見なす）
                if stop_keyword in line:

                    logging.info(f"停止キーワード '{stop_keyword}' が見つかりました。読み取り終了")

                    # 直ちにリターン
                    return new_lines, new_position

                # 2行以上の空白行が来たらループを抜ける
                if empty_lines >= 2:
                    exit_loop = True
                    break
        else:
            continue

        if exit_loop:
            break

        # 次のデータが来るまで待つ
        time.sleep(interval)

    logging.info("読み取り終了")
    return new_lines, new_position


# キーワードを含む行を特定してMy:Callsignを特定
def extract_identifier(line, keyword, start, end):

    callsign = None

    # キーワードを含む行を特定してキーワードの桁数を取得
    index = line.find(keyword)

    # キーワードが見つかったら
    if index != -1 and len(line) >= index + end:

        # キーワードの開始位置から3文字目以降(My:JE3HCZ  )８文字を読む
        callsign =  line[index + start: index + end]

    return callsign


# データフォルダ内のファイルを整理する
def cleanup_files(callsign_file, callsign):

    if callsign is None:
        return

    # データフォルダの定義
    folder_path = Path("/var/www/html/rpt/")

    # callsign名の付いたファイルの内ラストハードリストに無いものを削除
    with open('/var/www/html/rpt/lastheardusers.txt', 'r') as f:
        users = [line.rstrip().rstrip("\n") for line in f]

        # 除外するファイル
        keep_files = {"lastheardusers.txt", "oldesttime.txt", f"{callsign_file}.php", f"{callsign_file}.png", f"{callsign}.html"}
        logging.info(f"削除除外ファイル : {keep_files}")

        # フォルダ内のファイルを取得
        for file_name in os.listdir(folder_path):
            file_path = os.path.join(folder_path, file_name)

            # file_name が callsign で始まるかチェック
            if callsign and file_name.startswith(callsign):

                # 削除除外ファイルなら削除せずパス
                if file_name in keep_files:
                    continue

                # それ以外のコールサインごとに選択された過去のファイルは削除
                else:
                    os.remove(file_path)
                    logging.info(f"Deleted: {file_path}")


# 新規取得したデータをファイルに書き込む(callsign.html)
def update_data_store(new_lines, keyword1, keyword2, callsign_file):

    # 変数の定義／初期化
    callsign = None
    new_block = []

    # 取得したデータ
    for line in new_lines:

        if not callsign and keyword1 in line:

            start =  3
            end   = 11

            # コールサインを特定する
            callsign = extract_identifier(line, keyword1, start, end).rstrip()

        if not callsign and keyword2 in line:

            start =  5
            end   = 13
            callsign = extract_identifier(line, keyword2, start, end).rstrip()


        # `http://` から始まるURLを探し、相対パスに変換
        start = line.find('http://')

        if start != -1:

            # 最初の `>` を探す（URLの終わりを見つける）
            end = line.find('>', start)

            if end != -1:

                # URLの部分をファイル名だけの相対URLに置き換える
                url = line[start:end]

                # '/'で分割して[-1](最後)のファイル名の部分だけ取得してURLと置き換える
                filename = url.split('/')[-1]
                line = line.replace(url, f'{filename}', 1)

        # 各行をリストとして一つのブロックに追記
        new_block.append(line.rstrip())

    # コールサインをファイル名とする、データを書き込んだhtmlファイルを作成
    if callsign:

        # コールサインにスペースを含む場合アンダースコアに置き換える
        callsign = callsign.replace(" ", "_")
        logging.info(f"callsign: {callsign} (type: {type(callsign)})")

        with open('/var/www/html/rpt/' + callsign.rstrip() + '.html', 'w') as f:
            f.write('<html><body><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><pre>' + '\n')
            f.write('<br><br>'.join(new_block) + '<br><br>')
            f.write('</pre></body></html>')

        logging.info("Data store updated.")

        return callsign


# ログファイルの監視を開始する
def monitor_log(logfile, keyword1, keyword2, interval=5):

    # 抽出したファイル名を格納する変数(php,pngファイル)
    callsign_file = None

    # 初回の読み取り開始位置
    last_position = 0

    while True:

        # 新しいデータを取得し、新しい位置を前回の位置として保存
        new_lines, last_position = read_new_lines(logfile, last_position)

        # 追加データが取得できたらテキストファイルに保存
        if new_lines:

            # すべての行について処理する
            for line in new_lines:

                # /rpt/を含む行があればその最初の位置を取得
                start = line.find("/rpt/")

                # startが正常な値であったら、そこから５文字目がファイル名の始まり
                if start != -1:
                    start += 5

                    # 同様に拡張子の位置を取得
                    end = line.rfind(".php")

                    # start,end供に正常な値であったら
                    if end != -1 and start < end:
                        callsign_file = line[start:end]
                        logging.info(f"最新のデータファイル名 : {callsign_file}")

            callsign = update_data_store(new_lines, keyword1, keyword2, callsign_file)

        # 一定時間待機
        cleanup_files(callsign_file, callsign)
        time.sleep(interval)


# MAIN
if __name__ == "__main__":

    # ログ出力の設定
    logging.basicConfig(
        level=logging.DEBUG,
        format="%(asctime)s - %(levelname)s - %(message)s",
        handlers=[
            logging.FileHandler("/var/log/log2database.log", encoding="utf-8"),   # ファイル出力
            logging.StreamHandler()                                               # コンソール出力
        ]
    )

    # 変数の設定
    log_path = "/var/log/rpi-monitor.log"
    keyword1 = "My:"
    keyword2 = "from"

    logging.info("************************************************************************************************************" + "\n" +
                f"                                  rpi-monitorログの最新データよりデータベースファイルの作成するアプリケーション({app_name}.py ver.{app_ver})" + "\n" +
                 "                                 ************************************************************************************************************")

    # モニターの開始
    monitor_log(log_path, keyword1, keyword2)
