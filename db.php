<?php
//required files
require_once('class/db_'.DBMS_NAME.'.php');		//database class
require_once('class/sql_'.DBMS_NAME.'.php');	//sql class
require_once('class/dal.php');					//bl class

//create database connection
$myDbConn = new dbConnection;											//create db connection object
$myDbConn->init(DB_USERNAME,DB_PASSWORD,DB_DATABASE,DB_CONNECTION);
$dbc = $myDbConn->getConnString();										//get connection string

$myQuery = new dbQuery($dbc);		//create database query object
$mySQL = new dbSQL($dbc);			//create database sql object
$dal = new DAL($dbc);				//create data access layer object
?>