<?php

/*!
  \class eZMutex ezmutex.php
  \brief The class eZMutex provides a memcache based mutex. 

*/

class eZMutex
{
    const STEAL_STRING = '_eZMutex_Steal';

	private $mc;

    function eZMutex( $name )
    {
    	$ini = eZINI::instance( 'memcache.ini' );
        $mutexPath = eZDir::path( array( eZSys::cacheDirectory(), 'ezmutex' ) );

    	if (function_exists('memcache_pconnect')) {
			$this->mc = memcache_connect($ini->variable( 'MemcacheSettings', 'Server' ), $ini->variable( 'MemcacheSettings', 'Port' ));
		}
		if ($this->mc) {
		        $this->Name = eZDir::path( array( $mutexPath, $name ) );
		        $this->KeyName = $this->Name;
		        $this->FileName = $this->Name;
		        $this->MetaKeyName = $this->Name . '_meta';
		} else {
		        $this->Name = md5( $name );
		        eZDir::mkdir( $mutexPath, false, true );
		        $this->FileName = eZDir::path( array( $mutexPath, $this->Name ) );
		        $this->MetaFileName = eZDir::path( array( $mutexPath, $this->Name . '_meta' ) );
		}
    }
    
    function fp()
    {
        if ( !isset( $GLOBALS['eZMutex_FP_' . $this->FileName] ) ||
             $GLOBALS['eZMutex_FP_' . $this->FileName] === false )
        {
            $GLOBALS['eZMutex_FP_' . $this->FileName] = fopen( $this->FileName, 'w' );
            if ( $GLOBALS['eZMutex_FP_' . $this->FileName] === false )
            {
                eZDebug::writeError( 'Failed to open file: ' . $this->FileName );
            }
        }
        return $GLOBALS['eZMutex_FP_' . $this->FileName];
    }
    
    function test()
    {
		if ($this->mc) {
	        return memcache_get($this->mc,$this->KeyName);
	    } else {
	    	if ( $fp = $this->fp() )
	        {
	            if ( flock( $fp, LOCK_EX | LOCK_NB ) )
	            {
	                flock( $fp, LOCK_UN );
	                return false;
	            }
	        }
	        return true;
    	}
    }

    function lock( $time = 60 )
    {
		if ($this->mc) {
	        if ( memcache_add($this->mc,$this->KeyName,"1",false, $time) )
	        {

	            $this->clearMeta();
	            $this->setMeta( 'timestamp', time() );
	            return true;
	        }
	        return false;
		} else {	        
	        if ( $fp = $this->fp() )
	        {
	            if ( flock( $fp, LOCK_EX ) )
	            {
	                $this->clearMeta();
	                $this->setMeta( 'timestamp', time() );
	                return true;
	            }
	        }
	        return false;
		}
    }

    function setMeta( $key, $value )
    {
		if ($this->mc) {
	    	$content = memcache_get($this->mc,$this->MetaKeyName);
	    	$content[$key] = $value;
			memcache_set($this->mc,$this->MetaKeyName,$content,false,0);
		} else {
	        $tmpFile = $this->MetaFileName . substr( md5( mt_rand() ), 0, 8 );
	        $content = array();
	        if ( file_exists( $this->MetaFileName ) )
	        {
	            $content = unserialize( file_get_contents( $this->MetaFileName ) );
	        }
	        $content[$key] = $value;
	        eZFile::create( $tmpFile, false, serialize( $content) );
	        eZFile::rename( $tmpFile, $this->MetaFileName );			
		}
    }

    function meta( $key )
    {
		if ($this->mc) {
	    	$content = memcache_get($this->mc,$this->MetaKeyName);
	        return isset( $content[$key] ) ? $content[$key] : null;
		} else {
	        $content = array();
	        if ( file_exists( $this->MetaFileName ) )
	        {
	            $content = unserialize( file_get_contents( $this->MetaFileName ) );
	        }
	        return isset( $content[$key] ) ? $content[$key] : null;
    	}
    }

    function clearMeta()
    {
		if ($this->mc) {
	    	$content = array();
			memcache_set($this->mc,$this->MetaKeyName,$content,false,0);
		} else {
	        $tmpFile = $this->MetaFileName . substr( md5( mt_rand() ), 0, 8 );
	        $content = array();
	        eZFile::create( $tmpFile, false, serialize( $content) );
	        eZFile::rename( $tmpFile, $this->MetaFileName );
	    }
    }

    function unlock()
    {
		if ($this->mc) {
	    	memcache_delete($this->mc,$this->MetaKeyName, 0);
	    	memcache_delete($this->mc,$this->KeyName, 0);
	        return true;
		} else {
	        if ( $fp = $this->fp() )
	        {
	            fclose( $fp );
	            @unlink( $this->MetaFileName );
	            @unlink( $this->FileName );
	            $GLOBALS['eZMutex_FP_' . $this->FileName] = false;
	        }
	        return false;
	    }
    }

    function lockTS()
    {
        return $this->test() ? $this->meta( 'timestamp' ) : false;
    }

    function steal( $force = false )
    {
        $stealMutex = new eZMutex( $this->Name . eZMutex::STEAL_STRING );
        if ( !$force )
        {
            // Aquire a steal mutex, and steal the mutex.
            if ( $stealMutex->test() )
            {
                return false;
            }
            if ( $stealMutex->lock() )
            {
                $stealMutex->setMeta( 'pid', getmypid() );
                if ( $this->lock() )
                {
                    // sleep for 1 second in case lock has only been granted beacause a larger
                    // cleanup is in progress.
                    sleep( 1 );
                    $stealMutex->unlock();
                    return true;
                }
            }
        }
        else
        {
            $stealPid = $stealMutex->meta( 'pid' );
            if ( is_numeric( $stealPid ) &&
                 $stealPid != 0 &&
                 function_exists( 'posix_kill' ) )
            {
                eZDebug::writeNotice( 'Killing steal mutex process: ' . $stealPid );
                posix_kill( $stealPid, 9 );
            }

            // If other steal mutex exists, kill it, and create your own.
            $this->unlock();
            return $this->lock();
        }
        return false;
    }

    public $Name;
    public $FileName;
}

?>
