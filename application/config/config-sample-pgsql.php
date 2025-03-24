<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the 'Database Connection'
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|    'connectionString' Hostname, database, port and database type for
|     the connection. Driver example: mysql. Currently supported:
|                 mysql, pgsql, mssql, sqlite, oci
|    'username' The username used to connect to the database
|    'password' The password used to connect to the database
|    'tablePrefix' You can add an optional prefix, which will be added
|                 to the table name when using the Active Record class
|
*/
return array(
    'name' => 'LimeSurvey',
    'components' => array(
        'db' => array(
            'connectionString' => 'pgsql:host=localhost;port=5432;user=postgres;password=somepassword;dbname=limesurvey;',
            'emulatePrepare' => true,
            'username' => 'postgres',
            'password' => 'somepassword',
            'charset' => 'utf8',
            'tablePrefix' => 'lime_',
        ),

        // Uncomment the following lines if you need table-based sessions.
        // Note: Table-based sessions are currently not supported on MSSQL server.
        // 'session' => array (
            // 'class' => 'application.core.web.DbHttpSession',
            // 'connectionID' => 'db',
            // 'sessionTableName' => '{{sessions}}',
        // ),

        'urlManager' => array(
            'urlFormat' => 'get',
            'rules' => array(
            // You can put your own rules here
            ),
            'showScriptName' => true,
        ),

    ),
    // Use the following config variable to set modified optional settings copied from config-defaults.php
    'config'=>array(
        // debug: Set this to 1 if you are looking for errors. If you still get no errors after enabling this
        'debug'=>2, // Set debug to 2 to display all errors
        'debugsql'=>1, // Set this to 1 to enable SQL logging, only active when debug = 2
    )
);
/* End of file config.php */
/* Location: ./application/config/config.php */
