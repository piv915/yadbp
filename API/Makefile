# $Id$

BAPI_COMMON_SOURCES = *.php
BAPI_IMPL_SOURCES  = impl/*.php
#BAPI_IMPL_SOURCES  =- impl/DataObject.php

all: bapi

bapi: $(BAPI_COMMON_SOURCES) $(BAPI_IMPL_SOURCES)
	echo "<?php" > out/$(.TARGET).php
	echo '// $$Id$$ ' >> out/$(.TARGET).php
	cat $(.ALLSRC:S/impl\/DataObject.php//) | sed s/\<\?php//g | sed s/\?\>//g | sed s/\$$Id/CVS/g >> out/$(.TARGET).php
	rcs -l out/bapi.php
	ci out/bapi.php
	co out/bapi.php

inst: bapi
	install -m 664 out/bapi.php ../include/
	install -m 664 out/bapi.php ../../cron/lib/

install: bapi
	ssh -T -i /usr/local/home/username/.ssh/rsa_open username@10.0.20.16 'cat /js/jB/web/SiteName.ru/htdocs/tools/classes/bapi.php' > out/bapi.old.php
	rcs -l out/bapi.old.php
	ci out/bapi.old.php
	scp -i /usr/local/home/username/.ssh/rsa_open out/bapi.php username@10.0.20.16:/js/jB/web/SiteName.ru/htdocs/tools/classes/bapi.test.php

#$(BAPI_COMMON_SOURCES) $(BAPI_IMPL_SOURCES):
#	$(CC) $(CFLAGS) -c $*.inc -o $*.php


#bapi: $(BAPI_COMMON_SOURCES) $(BAPI_IMPL_SOURCES)



#	 cat $(.ALLSRC) | sed s/<\?php//g
#	 echo ${OUT}
