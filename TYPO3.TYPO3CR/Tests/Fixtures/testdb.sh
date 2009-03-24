#!/bin/bash

BASEPATH="./"

realpath() {
 local p=$1
 if [ "$(echo $p | sed -e 's/^\(.\).*$/\1/')" != "/" ]; then
   p="`pwd`/$p"
 fi
 echo $p | sed -e 's#/[^/]\+/\.\.##g';
}

usage() {
  echo "Usage $0 [ sqlite | postgres | mysql ] [ setup | reset | dump | cleanup ]"
  echo
  echo 'setup   Setup database and user (for mysql+postgresql) from .sql files'
  echo 'reset   Reset database to a clean state after a test (does not drop it, just deletes table content and reinserts testdata, not useful for testing code that creates new tables)'
  echo 'dump    Show current content of the test database'
  echo 'cleanup Remove test database and user'
  echo
  echo 'Use testdb.conf to configure usernames, passwords,...'
  exit 1
}

schema_sql() {
  case $1 in
    "sqlite")
      cat $BASEPATH/../../Resources/SQL/TYPO3CR_schema.sql | sed -e 's/"([0-9]\{0,\})/"/g';;
    "postgres")
      cat $BASEPATH/../../Resources/SQL/TYPO3CR_schema.sql | sed -e 's/"([0-9]\{0,\})/"/g';;
    "mysql")
      cat $BASEPATH/../../Resources/SQL/TYPO3CR_schema.sql;;
  esac
}

testdata_sql() {
  case $1 in
    "sqlite")
      cat $BASEPATH/TYPO3CR_testdata.sql;;
    "postgres")
      cat $BASEPATH/TYPO3CR_testdata.sql;;
    "mysql")
      cat $BASEPATH/TYPO3CR_testdata.sql;;
  esac
}

sqlite_setup() {
  echo Setting up sqlite test db
  sqlite_cleanup
  (schema_sql sqlite;testdata_sql sqlite) | $SQLITE3 $SQLITE3_DBFILE
}

sqlite_dump() {
  echo Dumping sqlite test db
  $SQLITE3 $SQLITE3_DBFILE .dump
}

sqlite_cleanup() {
  echo Cleaning up sqlite test db
  if [ -e $SQLITE3_DBFILE ]; then
    rm $SQLITE3_DBFILE
  fi
}

postgres_setup() {
  echo Setting up postgresql test db
  postgres_cleanup
  PGPASSWORD="$pgsqlrootpw" $PSQL -U postgres -c "CREATE ROLE $PGSQL_USER PASSWORD '$PGSQL_PASS' CREATEDB LOGIN;"
  PGPASSWORD="$PGSQL_PASS" $PSQL -U $PGSQL_USER -d template1 -c "CREATE DATABASE \"$PGSQL_DB\";"
  (schema_sql postgres;testdata_sql postgres) | PGPASSWORD="$PGSQL_PASS" $PSQL -q -U $PGSQL_USER -d $PGSQL_DB
}

postgres_reset() {
  echo Resetting postgresql test db
  testdata_sql postgres | PGPASSWORD="$PGSQL_PASS" $PSQL -q -U $PGSQL_USER -d $PGSQL_DB
}

postgres_dump() {
  echo Dumping postgresql test db
  for table in `PGPASSWORD="$PGSQL_PASS" $PSQL -U $PGSQL_USER -d $PGSQL_DB -c '\dt' | grep "public" | awk '{print $3}'`
  do
    echo Table $table:
    echo
    PGPASSWORD="$PGSQL_PASS" $PSQL -U $PGSQL_USER -d $PGSQL_DB -c "SELECT * FROM $table;"
  done
}

postgres_cleanup() {
  echo Cleaning up postgresql test db
  if PGPASSWORD="$pgsqlrootpw" $PSQL -U postgres -c '\l' | grep "\b$PGSQL_DB\b" > /dev/null; then
    PGPASSWORD="$pgsqlrootpw" $PSQL -U postgres -c "DROP DATABASE $PGSQL_DB;"
  fi
  if PGPASSWORD="$pgsqlrootpw" $PSQL -U postgres -c '\du' | grep "\b$PGSQL_USER\b" > /dev/null; then
    PGPASSWORD="$pgsqlrootpw" $PSQL -U postgres -c "DROP ROLE $PGSQL_USER;"
  fi
}

mysql_setup() {
  echo Setting up mysql test db

$MYSQL -u root -p"$mysqlrootpw" <<EOF
DROP DATABASE IF EXISTS $MYSQL_DB;

CREATE DATABASE $MYSQL_DB;
GRANT ALL PRIVILEGES ON $MYSQL_DB.* TO '$MYSQL_USER'@'localhost' IDENTIFIED BY '$MYSQL_PASS';
EOF
  (echo "SET SESSION sql_mode='ANSI_QUOTES';";schema_sql mysql;testdata_sql mysql) | $MYSQL -u $MYSQL_USER -p$MYSQL_PASS -D $MYSQL_DB
}

mysql_reset() {
  echo Resetting mysql test db
  (echo "SET SESSION sql_mode='ANSI_QUOTES';";testdata_sql mysql) | $MYSQL -u $MYSQL_USER -p$MYSQL_PASS -D $MYSQL_DB
}

mysql_dump() {
  echo Dumping mysql test db
  for table in $(echo "show tables;" | $MYSQL -u $MYSQL_USER -p$MYSQL_PASS -D $MYSQL_DB | grep -v "Tables_in")
  do
    echo Table $table;
    echo
    echo "select * from $table;" | $MYSQL -u $MYSQL_USER -p$MYSQL_PASS -D $MYSQL_DB
  done
}

mysql_cleanup() {
  echo Cleaning up mysql test db

  $MYSQL -u root -p"$mysqlrootpw" <<EOF
DROP DATABASE IF EXISTS $MYSQL_DB;
DROP USER '$MYSQL_USER'@'localhost';
EOF
}

BASEPATH="$(dirname $(realpath $0))"

source $BASEPATH/testdb.conf

if [ "$(basename $SQLITE3_DBFILE)" = "$SQLITE3_DBFILE" ]; then
  SQLITE3_DBFILE="$BASEPATH/$SQLITE3_DBFILE"
fi

pgsqlrootpw=""
mysqlrootpw=""

if [ "$#" = "3" ]; then
  if [ "$1" = "postgres" ]; then
    pgsqlrootpw="$3"
  else
    if [ "$1" = "mysql" ]; then
      mysqlrootpw="$3"
    else
      usage
    fi
  fi
else
  if [ "$#" != "2" ]; then
    usage
  fi
fi

if [ "$1" = "mysql" -a "$2" != "reset" ]; then
  if [ -z "$mysqlrootpw" ]; then
    echo -n "Please enter your MySQL root password (no * or anything else is echoed): "
    read -s mysqlrootpw
  fi
  while ! $MYSQL -u root -p"$mysqlrootpw" -e "show databases;" > /dev/null
  do
    echo "No working mysql root password known..."
    echo -n "Please enter your MySQL root password (no * or anything else is echoed): "
    read -s mysqlrootpw
  done
  echo
fi

if [ "$1" = "postgres" -a "$2" != "reset" ]; then
  if [ -z "$pgsqlrootpw" ]; then
    echo -n "Please enter your PostgreSQL postgres user password (no * or anything else is echoed): "
    read -s pgsqlrootpw
  fi
  while ! PGPASSWORD="$pgsqlrootpw" $PSQL -U postgres -c "\dt" > /dev/null
  do
    echo "No working PostgreSQL postgres user password known..."
    echo -n "Please enter your PostgreSQL postgres user password (no * or anything else is echoed): "
    read -s pgsqlrootpw
  done
  echo
fi

case $1 in
  "sqlite")
    test -z "$SQLITE3" && echo "SQLITE3 not set in testdb.conf, can not continue" && exit 1
    test -z "$SQLITE3_DBFILE" && echo "SQLITE3_DBFILE not set in testdb.conf, can not continue" && exit 1
    case $2 in
      "setup")
        sqlite_setup;;
      "reset")
        sqlite_setup;;
      "dump")
        sqlite_dump;;
      "cleanup")
        sqlite_cleanup;;
      *)
        usage;;
    esac;;
  "postgres")
    test -z "$PSQL" && echo "PSQL not set in testdb.conf, can not continue" && exit 1
    test -z "$PGSQL_USER" && echo "PGSQL_USER not set in testdb.conf, can not continue" && exit 1
    test -z "$PGSQL_PASS" && echo "PGSQL_PASS not set in testdb.conf, can not continue" && exit 1
    test -z "$PGSQL_DB" && echo "PGSQL_DB not set in testdb.conf, can not continue" && exit 1
    case $2 in
      "setup")
        postgres_setup;;
      "reset")
        postgres_reset;;
      "dump")
        postgres_dump;;
      "cleanup")
        postgres_cleanup;;
      *)
        usage;;
    esac;;
  "mysql")
    test -z "$MYSQL" && echo "MYSQL not set in testdb.conf, can not continue" && exit 1
    test -z "$MYSQL_USER" && echo "MYSQL_USER not set in testdb.conf, can not continue" && exit 1
    test -z "$MYSQL_PASS" && echo "MYSQL_PASS not set in testdb.conf, can not continue" && exit 1
    test -z "$MYSQL_DB" && echo "MYSQL_DB not set in testdb.conf, can not continue" && exit 1
    case $2 in
      "setup")
        mysql_setup;;
      "reset")
        mysql_reset;;
      "dump")
        mysql_dump;;
      "cleanup")
        mysql_cleanup;;
      *)
        usage;;
    esac;;
  *)
    usage;;
esac

