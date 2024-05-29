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


if __name__ == "__main__":
	addr = "127.0.0.1"
	port = "20202"
	url  = "http://" + addr + ":" + port
	get_html(url)

