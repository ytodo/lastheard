###########################################################
#
#	Program name : mqtt_rebooter.py
#
#	メッセージが合致して場合 reboot コマンドを実行
#
#					Create by Y.Todo 2025.6.29
#					Latest update :  2025.6.29
###########################################################
import os
import platform
import paho.mqtt.client as mqtt

# AlmaLinux でのみ必要なもの
try:
	from paho.mqtt.client import CallbackAPIVersion
except ImportError:
	CallbackAPIVersion = None

# 基本変数の設定
MQTT_BROKER   = "localhost"             # またはブローカーのIP
MQTT_TOPIC    = "raspi/control/reboot"
MQTT_PASSWORD = "REBOOT"

# WEBからの入力データが基本データと一致したらコマンドを実行
def on_message(client, userdata, msg):
	if msg.topic == MQTT_TOPIC:
		if msg.payload.decode() == MQTT_PASSWORD:
			os.system("sudo reboot")

# mqtt.Client()に変わる関数として定義
def get_mqtt_client():

	# 変数の初期化
	use_version2 = False

	# OSがAlmaLinuxなら詳細を調査
	try:
		with open("/etc/os-release") as f:
			os_info = f.read()
			if ("debian" not in os_info.lower()) and CallbackAPIVersion is not None:
				use_version2 = True
	except Exception as e:
		print(f"OS判定中にエラー: {e}")

	# バージョン2のAPIを使うかどうかで分岐
	if use_version2:
		return mqtt.Client(protocol=mqtt.MQTTv311, callback_api_version=CallbackAPIVersion.VERSION2)
	else:
		return mqtt.Client(protocol=mqtt.MQTTv311)

client = get_mqtt_client()				# 通常は mqtt.Client() の括弧の中に returnに続く式を記入する所を関数化している
client.connect(MQTT_BROKER, 1883, 60)
client.subscribe(MQTT_TOPIC)
client.on_message = on_message
client.loop_forever()
