sampa
=====

Autoloading
-----------
When using autoloading, sampa will resolve addresses like 
localhost/MyModule/MyAction to App\MyAppname\MyModuleController and call 
MyModuleController->MyAction, so you have to add the prefix "App" to your 
autoloading routine (see dist/index.php.dist).
The same approach is required for Workers, when running a worker, sampa will 
resolve parameters like MyNamespace\MyWorker to Worker\MyNamespace\MyWorker 
(see dist/boss.php.dist).

Folders
-------
During Kernel/Boss boot, you can setup the path to your *.config.php files and 
the path to log files, by using the first and second parameters respectively 
(see dist/index.php.dist and dist/boss.php.dist).
