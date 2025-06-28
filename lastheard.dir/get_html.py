#################################################################
#                                                               #
#   multi_forwardのWEBを取得してindex.phpのユーザ取得に提供     #
#                                                               #
#                                Created by Y.Todo / JE3HCZ     #
#################################################################
import requests
import time

# 指定アドレス・指定ポートからhtmlを取得する関数
def get_html(url):

    try:
        response = requests.get(url)
        response.raise_for_status()  # HTTPステータスコードが200番台以外の場合に例外を発生させる
        print(f"{response.text}\n")

    except requests.exceptions.RequestException as e:
        print(f"Error: {e}")

# OSの種類を取
def get_os_id():

    # OS変数の初期化
    os_id = None

    # OSの種類を取得してos_idに代入する
    try:
        with open('/etc/os-release', 'r') as file:
            for line in file:
                if line.startswith('ID='):
                    os_id = line.strip().split('=')[1].strip('"')
                    break

    # OS種類が取得できない時
    except FileNotFoundError:
        os_id = "Unknown"

    return os_id

if __name__ == "__main__":

    # OSを取得
    os_id = get_os_id()

    # OS特有のポートを代入
    if (os_id == "debian"):
        port = '20202'
    else:
        port = '20202'

    addr = "127.0.0.1"
    url  = "http://" + addr + ":" + port
    get_html(url)
