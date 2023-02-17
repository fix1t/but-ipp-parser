all: parse tests

.PHONY: all tests parse

parse: 
	php parser.php --help me
 
tests:
	php test.php header