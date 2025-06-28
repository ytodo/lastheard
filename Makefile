###################################################
# Makefile for compiling recv for lastheard
#       and other related files
#
#                      Created by Yosh Todo/JE3HCZ
#                      Latest Update : 2025.6.26
###################################################

# Program Name and object files
PROGRAM	= lastheard
OBJECTS	= recv.o
USER_NAME = $(shell whoami)

# Redefine MACRO
CC		= gcc

# Define extention of Suffix Rules
.SUFFIXES	: .c .o

# Rule of compiling program
$(PROGRAM)	: $(OBJECTS)
	$(CC) -o $(PROGRAM) $^

# Sufix Rule
.c.o	:
	$(CC) -c $<

# Target of Delete files
.PHONY	: clean
clean	:
	$(RM)  $(PROGRAM) $(OBJECTS)

# Dependency of Header Files
$(OBJECTS)	: recv.h

# OSの判別
DISTRO 	:= $(shell . /etc/os-release && echo $$ID)

print-distro:
	@echo "DISTRO = $(DISTRO)"

### Install files ###
install :

# プログラムのコンパイル
	@echo "コンパイルしています..."
	@make > /dev/null

# プログラムの配置
	@echo "プログラム・ファイルを配置しています..."
	@sudo mv $(PROGRAM)				/usr/local/bin
	@sudo cp -rf ./html/			/var/www
	@sudo mkdir -p /var/www/lastheard
	@sudo cp -rf ./lastheard.dir	/var/www/lastheard
	@sudo chown $(USER_NAME):$(USER_NAME) /var/www/lastheard

ifeq ($DISTRO),debian)
	@sudo apt update
	@sudo apt install -y python3-pip
	@sudo apt install -y mosquitto mosquitto-clients
	@sudo apt install -y php-mbstring
	@cd /var/www/lastheard && \
	python3 -m venv venv && \
	. venv/bin/activate && \
	pip3 install paho-mqtt
else
	@sudo dnf install -y python3-pip
	@sudo dnf install -y mosquitto mosquitto-devel
	@sudo dnf install -y php-mbstring
	@pip3 install paho-mqtt
endif

# ユニットファイルの配置
	@echo "ユニットファイルを配置しています..."
ifeq ($(DISTRO), debian)
	@sudo cp ./unitfiles/lastheard.service.Rpi			/etc/systemd/system/lastheard.service
	@sudo cp ./unitfiles/mqtt_rebooter.service.Rpi		/etc/systemd/system/mqtt_rebooter.service
	@sudo cp ./unitfiles/log2database.service			/etc/systemd/system
else
	@sudo cp ./unitfiles/lastheard.service.Alma			/etc/systemd/system/lastheard.service
	@sudo cp ./unitfiles/mqtt_rebooter.service.Alma		/etc/systemd/system/mqtt_rebooter.service
endif

# serviceの起動設定
	@echo "サービスの有効化（自動起動）を設定しています..."
	@sudo systemctl daemon-reload
	@sudo systemctl enable lastheard.service		> /dev/null
	@sudo systemctl start  lastheard.service
	@sudo systemctl enable mosquitto.service		> /dev/null
	@sudo systemctl start  mosquitto.service
	@sudo systemctl enable mqtt_rebooter.service	> /dev/null
	@sudo systemctl start  mqtt_rebooter.service

ifeq ($(DISTRO), debian)
	@sudo systemctl enable log2database.service		> /dev/null
	@sudo systemctl start  log2database.service
endif

### ファイルのアップデート
update  :

# プログラムのダウンロードとファイルの配置
	@echo "アップデートのチェックとコンパイルをします..."
	@git pull
	@make > /dev/null
	@sudo mv $(PROGRAM)					/usr/local/bin
	@sudo cp ./html/*.php				/var/www/html
	@sudo cp ./html/favicon.ico			/var/www/html
	@sudo cp -rf ./html/css				/var/www/html
	@sudo mkdir -p /var/www/lastheard
	@sudo cp ./lastheard.dir/*.py		/var/www/lastheard
	@sudo cp ./lastheard.dir/*.php		/var/www/lastheard
	@sudo cp ./lastheard.dir/.htaccess	/var/www/lastheard
	@sudo chown $(USER_NAME):$(USER_NAME) /var/www/lastheard

ifeq ($(DISTRO),debian)
	@sudo apt update
	@sudo apt install -y python3-pip
	@sudo apt install -y mosquitto mosquitto-clients
	@sudo apt install -y php-mbstring
	@cd /var/www/lastheard && \
	python3 -m venv venv && \
	. venv/bin/activate && \
	pip3 install paho-mqtt
else
	@sudo dnf install -y python3-pip
	@sudo dnf install -y mosquitto mosquitto-devel
	@sudo dnf install -y php-mbstring
	@pip3 install paho-mqtt
endif


# ユニットファイルの配置
	@echo "ユニットファイルを配置しています..."
ifeq ($(DISTRO), debian)
	@sudo cp ./unitfiles/lastheard.service.Rpi			/etc/systemd/system/lastheard.service
	@sudo cp ./unitfiles/mqtt_rebooter.service.Rpi		/etc/systemd/system/mqtt_rebooter.service
	@sudo cp ./unitfiles/log2database.service			/etc/systemd/system
else
	@sudo cp ./unitfiles/lastheard.service.Alma			/etc/systemd/system/lastheard.service
	@sudo cp ./unitfiles/mqtt_rebooter.service.Alma		/etc/systemd/system/mqtt_rebooter.service
endif

# serviceの起動設定
	@echo "サービスの有効化（自動起動）を設定しています..."
	@sudo systemctl daemon-reload
	@sudo systemctl enable  lastheard.service		> /dev/null
	@sudo systemctl restart lastheard.service
	@sudo systemctl enable mosquitto.service        > /dev/null
	@sudo systemctl restart  mosquitto.service
	@sudo systemctl enable mqtt_rebooter.service    > /dev/null
	@sudo systemctl restart  mqtt_rebooter.service
	@sudo systemctl enable  mqtt_rebooter.service	> /dev/null
	@sudo systemctl restart mqtt_rebooter.service

ifeq ($(DISTRO), debian)
	@sudo systemctl enable  log2database.service	> /dev/null
	@sudo systemctl restart log2database.service
endif
