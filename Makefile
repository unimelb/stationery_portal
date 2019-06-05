SU := /setup/
IN := /includes/
INC := .inc.php
# Assume the Makefile is at the top level of the repository
current_dir := $(shell pwd)
include $(current_dir)$(SU)stationery.conf
# the stationery.conf include file is the key configuration variable collection
production: ldapconnect$(INC) libpath$(INC) dbconnect$(INC) passport.php login_session_updater.class.php
	cd includes

ldapconnect$(INC): 
	cp $(SU)ldapconnect ldapconnect$(INC)

install: config

includes:
	mkdir -p $(current_dir)/includes

clean:
	rm -rf $(current_dir)/includes

config: includes $(current_dir)$(IN)dbconnect$(INC) $(current_dir)$(IN)chili$(INC) $(current_dir)$(IN)email_admin$(INC) $(current_dir)$(IN)libpath$(INC) $(current_dir)$(IN)ldapconnect$(INC) $(current_dir)$(IN)storage$(INC)


$(current_dir)$(IN)dbconnect$(INC):
	cp $(current_dir)$(SU)dbconnect $(current_dir)$(IN)dbconnect$(INC)
	sed -ri "s/(DBCONNECT\", ')[^:]+.*/\1$(STATIONERY_DBTYPE):host=$(STATIONERY_DBHOST);dbname=$(STATIONERY_DBNAME)'\);/" $@
	sed -ri "s/(DBUSER\", ').*/\1$(STATIONERY_DBUSER)'\);/" $@
	sed -ri "s/(DBPASS\", ').*/\1$(STATIONERY_DBPASS)'\);/" $@

$(current_dir)$(IN)chili$(INC):
	cp $(current_dir)$(SU)chili $(current_dir)$(IN)chili$(INC)

$(current_dir)$(IN)email_admin$(INC):
	cp $(current_dir)$(SU)email_admin $(current_dir)$(IN)email_admin$(INC)
	sed -ri "s/(ADMIN_EMAIL\", ').*/\1$(STATIONERY_ADMIN_EMAIL)'\);/" $@

$(current_dir)$(IN)libpath$(INC):
	cp $(current_dir)$(SU)libpath $(current_dir)$(IN)libpath$(INC)
	sed -ri "s/(LIBPATH\", ').*/\1$(STATIONERY_LIBPATH)'\);/" $@

$(current_dir)$(IN)ldapconnect$(INC):
	cp $(current_dir)$(SU)ldapconnect $(current_dir)$(IN)ldapconnect$(INC)
	sed -ri "s/(LDAP_CONNECTION\", ').*/\1$(STATIONERY_LDAP_CONNECTION)'\);/" $@
	sed -ri "s/(LDAP_DN\", ').*/\1$(STATIONERY_LDAP_DN)'\);/" $@

$(current_dir)$(IN)storage$(INC):
	cp $(current_dir)$(SU)storage $(current_dir)$(IN)storage$(INC)
	sed -ri "s/(FILESTORE\", ).*/\1$(subst /,'.DIRECTORY_SEPARATOR.',($(current_dir)$(STATIONERY_FILESTORE)))'\);/" $@
	sed -ri "s/(FILESTORE\", )\('\.(.*)/\1\2/" $@

.PHONY: install clean config



