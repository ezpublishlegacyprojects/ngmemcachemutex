Netgen Memcache Mutex extension
===================================
This extension overrides eZMutex to use memcache instead of file locking. 
Additionaly it overrides eZRunCronjobs class to set longer expiry time for lock (2 * eZRunCronjobs_MaxScriptExecutionTime).
There is also a simple memcache stats view (/memcache/stats) which is showing a bit of information about your memcache instance (we used some code from http://projects.ez.no/lamemcache for this).

Could be helpful for web sites which have a lot off mutex files (slowing down apache access to mutex dir) and for web sites on cluster which share var folder on network shared device (causing flock() system call to be very slow).
Otherwise nn normal web sites it will not have any significant impact.

Extension will fallback to file based mutex if memcached for some reason is not available.

