###################################################
# Makefile for compiling recv for lastheard
#       and other related files
#
#                      Created by Yosh Todo/JE3HCZ
#                      Latest Update : 2025.6.26
###################################################

# Program Name and object files
PROGRAM	= lastheard
OBJECTS = recv.o
DEST    = /usr/local/bin

# Redefine MACRO
CC      = gcc

# Define extention of Suffix Rules
.SUFFIXES   : .c .o

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
DISTRO := $(shell . /etc/os-release && echo $$ID)

### Install files ###
install :

# プログラムのコンパイル
    @echo "コンパイルしています..."
    @make > /dev/null

# プログラムの配置
    @echo "ファイルを配置しています..."
    @sudo mv $(PROGRAM)             /usr/local/bin/
    @sudo cp -rf ./html/            /var/www/
    @sudo cp -rf ./lastheard        /var/www/

# ユニットファイルの配置
    @echo "ユニットファイルを配置しています..."
ifeq ($(DISTRO), debian)
    @sudo cp lastheard.service.Rpi  /etc/systemd/system/lastheard.service
else
    @sudo cp lastheard.service.Alma /etc/systemd/system/lastheard.service
endif
    @sudo cp log2database.service   /etc/systemd/system/
    @sudo cp mqtt_rebooter.service  /etc/systemd/system/

# serviceの起動設定
    @echo "サービスの有効化（自動起動）を設定しています..."
    @sudo systemctl enable lastheard.service        > /dev/null
    @sudo systemctl start  lastheard.service
ifeq ($(DISTRO), debian)
    @sudo systemctl enable log2database.service     > /dev/null
    @sudo systemctl start  log2database.service
endif
    @sudo systemctl enable mqtt_rebooter.service    > /dev/null
    @sudo systemctl start  mqtt_rebooter.service

### ファイルのアップデート
update  :

# プログラムのダウンロードとコンパイル
    @echo "アップデートのチェックとコンパイルをします..."
    @git pull
    @make > /dev/null
    @sudo mv $(PROGRAM)             /usr/local/bin/
    @sudo cp ./html/*.php           /var/www/html/
    @sudo cp ./html/favicon.ico     /var/www/html/
    @sudo cp -rf ./html/css/        /var/www/html/
    @sudo cp ./lastheard/*.py       /var/www/lastheard/
    @sudo cp ./lastheard/*.php      /var/www/lastheard/
    @sudo cp ./lastheard/.htaccess  /var/www/lastheard/

# ユニットファイルの配置
    @echo "ユニットファイルを配置しています..."
ifeq ($(DISTRO), debian)
    @sudo cp lastheard.service.Rpi  /etc/systemd/system/lastheard.service
else
    @sudo cp lastheard.service.Alma /etc/systemd/system/lastheard.service
endif
    @sudo cp log2database.service   /etc/systemd/system/
    @sudo cp mqtt_rebooter.service  /etc/systemd/system/

# serviceの起動設定
    @echo "サービスの有効化（自動起動）を設定しています..."
    @sudo systemctl enable lastheard.service        > /dev/null
    @sudo systemctl start  lastheard.service
ifeq ($(DISTRO), debian)
    @sudo systemctl enable log2database.service     > /dev/null
    @sudo systemctl start  log2database.service
endif
    @sudo systemctl enable mqtt_rebooter.service    > /dev/null
    @sudo systemctl start  mqtt_rebooter.service
