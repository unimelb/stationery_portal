SU := /setup/
IN := /includes/
INC := .inc.php
# Assume the Makefile is at the top level of the repository
current_dir := $(shell pwd)
include $(current_dir)$(SU)stationery.conf
# the stationery.conf include file is the key configuration variable collection
install: config dataviews

includes:
	mkdir -p $(current_dir)/includes

dataviews:
	@echo "Adding views to database"
	mysql -u $(STATIONERY_DBNAME) -h $(STATIONERY_DBHOST) -p$(STATIONERY_DBPASS) $(STATIONERY_DBNAME) <$(current_dir)$(SU)stationery015.sql

output:
	mkdir -p $(current_dir)$(STATIONERY_FILESTORE)

clean:
	rm -rf $(current_dir)/includes

config: includes output $(current_dir)$(IN)dbconnect$(INC) $(current_dir)$(IN)chili$(INC) $(current_dir)$(IN)email_admin$(INC) $(current_dir)$(IN)libpath$(INC) $(current_dir)$(IN)ldapconnect$(INC) $(current_dir)$(IN)storage$(INC) $(current_dir)$(IN)login_session_updater.class.php $(current_dir)$(IN)passport.php


$(current_dir)$(IN)dbconnect$(INC):
	cp $(current_dir)$(SU)dbconnect $(current_dir)$(IN)dbconnect$(INC)
	sed -ri "s/(DBCONNECT\", ')[^:]+.*/\1$(STATIONERY_DBTYPE):host=$(STATIONERY_DBHOST);dbname=$(STATIONERY_DBNAME)'\);/" $@
	sed -ri "s/(DBUSER\", ').*/\1$(STATIONERY_DBUSER)'\);/" $@
	sed -ri "s/(DBPASS\", ').*/\1$(STATIONERY_DBPASS)'\);/" $@

$(current_dir)$(IN)chili$(INC):
	cp $(current_dir)$(SU)chili $(current_dir)$(IN)chili$(INC)
	sed -ri "s/(CHILI_APP\", ').*/\1$(CHILI_APP)'\);/" $@
	sed -ri "s/(CHILI_ENV\", ').*/\1$(CHILI_ENV)'\);/" $@
	sed -ri "s/(CHILI_WS\", ').*/\1$(CHILI_WS)'\);/" $@
	sed -ri "s/(CHILI_PROOF\", ').*/\1$(CHILI_PROOF)'\);/" $@
	sed -ri "s/(CHILI_PRINT\", ').*/\1$(CHILI_PRINT)'\);/" $@

$(current_dir)$(IN)login_session_updater.class.php:
	cp $(current_dir)$(SU)login_session_updater $@

$(current_dir)$(IN)passport.php:
	cp $(current_dir)$(SU)passport $@

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
	sed -ri "s/(FILEURL\", ').*/\1$(STATIONERY_FILEURL)'\);/" $@

.PHONY: install clean config



