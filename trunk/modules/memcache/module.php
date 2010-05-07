<?php
$Module = array(
	'name'            => 'memcache',
    'variable_params' => true
);

$ViewList         = array();
$ViewList['stats'] = array(
    'script'                  => 'stats_server.php',
	'functions'               => array( 'private' )
);



$FunctionList = array();
$FunctionList['private'] = array( );
?>