import paho.mqtt.client as mqtt
import os

MQTT_BROKER   = "localhost"             # またはブローカーのIP
MQTT_TOPIC    = "raspi/control/reboot"
MQTT_PASSWORD = "REBOOT"

def on_message(client, userdata, msg):
    if msg.topic == MQTT_TOPIC:
        if msg.payload.decode() == MQTT_PASSWORD:
            os.system("sudo reboot")

client = mqtt.Client()
client.connect(MQTT_BROKER, 1883, 60)
client.subscribe(MQTT_TOPIC)
client.on_message = on_message
client.loop_forever()
