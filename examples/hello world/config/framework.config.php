<?php
/*
*	configuration file
*	------------------
*
*	environment-based configuration can be done as follow:
*	$_sampa = array(
*		'environment1' => array(
*			...
*		),
*		...
*		'environmentN' => array(
*			...
*		)
*	);
*/
$_sampa = array(
	/*
	*	application configuration
	*	-------------------------
	*	basic application configurations are set at this point.
	*	multiple application can be setup as follow:
	*		'app' => array(
	*			'app1' => array(
	*				...
	*			),
	*			...
	*			'appN' => array(
	*				...
	*			)
	*		)
	*
	*	default_module: module that will be used when no module is provided by url (e.g. requesting /)
	*	domain: a FQDN (e.g. example.com)
	*	web_path: define the path prepended to web requests
	*		use this whenever your installation is not on the root of your domain
	*	rest: define when the application should behave as a restful application or not
	*		restfull applications use the HTTP request method as action name
	*	templates: path to templates folder
	*/
	'app' => array(
		'default_module' => 'main',
		'domain' => 'localhost',
		'web_path' => '/',
		'rest' => false,
		'templates' => dirname(__DIR__) . '/template'
	),
	/*
	*	base configuration
	*	------------------
	*	setup for <base /> tag.
	*	can be omitted to use application's domain/web_path.
	*
	*	domain: a FQDN (e.g. example.com)
	*	path: relative path to files (e.g. /)
	*/
	/*
	* omitting base configuration
	*	'base' => array(
	*		'domain' => 'localhost',
	*		'path' => '/'
	*	),
	*/
	/*
	*	cache configuration
	*	-------------------
	*	setup for application cache (response cache, cache helper).
	*
	*	driver: cache driver to be used
	*		can be one of the following:
	*		 - DISABLED
	*		 - FCACHE (cache on disk)
	*		 - MCACHE (depends on memcache extension)
	*		 - XCACHE (depends on xcache extension)
	*	host: cache server host (for MCACHE)
	*		can be omitted for default host
	*	port: cache server port (for MCACHE)
	*		can be omitted for default port
	*/
	'cache' => array(
		'driver' => sampa\Core\Cache::DISABLED,
		'host' => '127.0.0.1',
		'port' => 11211
	),
	/*
	*	log configuration
	*	-----------------
	*	setup for framework/application logging system.
	*
	*	level: sets the minimum level for logging
	*		can be one of the following:
	*		 - DISABLED
	*		 - EMERGENCY
	*		 - ALERT
	*		 - CRITICAL
	*		 - ERROR
	*		 - WARNING
	*		 - NOTICE
	*		 - INFO
	*		 - DEBUG
	*		 - ALL
	*	buffered: use of buffered writes to avoid concurrent writes to be mixed
	*/
	'log' => array(
		'level' => sampa\Core\Log::ALL,
		'buffered' => true
	),
	/*
	*	main configuration
	*	------------------
	*	framework main configuration.
	*
	*	include_path: change default include path (useful when having problems with PEAR libraries)
	*	timezone: timezone configuration for time and date functions
	*	debug: display errors and alerts
	*/
	'main' => array(
		'include_path' => '',
		'timezone' => 'America/Sao_Paulo',
		'debug' => true
	),
	/*
	*	session configuration
	*	---------------------
	*	user session configuration.
	*
	*	name: session name (used for cookie name too)
	*	subdomain: sets if session should be valid for all subdomains
	*	idle: max session idle time
	*	secure: use encrypted session handler to store values
	*	secure_key: key used to encrypt data when using secure session
	*	ssl: sets secure flag for cookie to be used only over HTTPS
	*/
	'session' => array(
		'name' => 'sampa',
		'subdomain' => false,
		'idle' => 1800,
		'secure' => false,
		'secure_key' => '',
		'ssl' => false
	)
);
