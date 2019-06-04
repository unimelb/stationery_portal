SU := /setup/
IN := /includes/
INC := .inc.php
current_dir := $(shell pwd)
include $(current_dir)$(SU)stationery.conf

production: ldapconnect$(INC) libpath$(INC) dbconnect$(INC) passport.php login_session_updater.class.php
	cd includes

ldapconnect$(INC): 
	cp $(SU)ldapconnect ldapconnect$(INC)

install: config

includes:
	mkdir -p $(current_dir)/includes
	#set -a
	#. $(current_dir)$(SU)stationery.conf
	#set +a

clean:
	rm -rf $(current_dir)/includes

config: includes $(current_dir)$(IN)dbconnect$(INC)


$(current_dir)$(IN)dbconnect$(INC):
	cp $(current_dir)$(SU)dbconnect $(current_dir)$(IN)dbconnect$(INC)
	sed -ri "s/(DBCONNECT\", ')[^:]+.*/\1$(STATIONERY_DBTYPE):host=$(STATIONERY_DBHOST);dbname=$(STATIONERY_DBNAME)'\);/" $(current_dir)$(IN)dbconnect$(INC)
	sed -ri "s/(DBUSER\", ').*/$(STATIONERY_DBUSER)'\);/" $(current_dir)$(IN)dbconnect$(INC)
	sed -ri "s/(DBPASS\", ').*/$(STATIONERY_DBPASS)'\);/" $(current_dir)$(IN)dbconnect$(INC)
.PHONY: install clean config



