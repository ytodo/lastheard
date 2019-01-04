###################################################
# Makefile for compiling recv for lastheard
#                      Created by Yosh Todo/JE3HCZ
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
