<?php

/**
 * A Twitter class to display and update tweets that can be quickly shown on a website,
 * observing Twitter's current API limit and caching downloaded Tweets automatically
 *
 * @author Jamie Hurst
 * @version 1.0
 */
class QuickTwitter {
    
    /**
     * CONFIGURATION
     * Set up current Twitter environment and any configuration
     */
    
    // Set the cache folder and the time it takes before the cache has expired
    private $cacheFolder = './cache/';   // This folder must exist and be writable by the web server
    private $cacheExpiration = 0;    // 1 HOUR
    
    // The maximum number of tweets that will be served at any one time
    private $maxTweets = 1;
    
    // This will not normally need to be changed
    private $twitterUrl = 'http://api.twitter.com/1/statuses/user_timeline.xml?screen_name=';
    
    /**
     * Fetch the latest tweets from Twitter and add them to the cache
     *
     * @param string $username Twitter username
     * @param string $password [Optional] Twitter password, only needed for protected tweets
     * @return array|string Tweets pulled from cache combined with latest download, or an error
     */
    public function fetchTweets($username, $password = null) {
        
        // Check if a username and password have been provided
        if(empty($username)) {
            return 'Error: You have not provided your Twitter username.';
        }
        
        // Check cache for expiration
        if(is_file($this->cacheFolder . $username . '.xml') && time() - filemtime($this->cacheFolder . $username . '.xml') <= $this->cacheExpiration) {
            
            // Cache is still current, return the current cached tweets
            return $this->readCache($username);
        }
        
        // Protected tweets require basic HTTP authentication, but public ones do not
        if(!is_null($password)) {
            // Set up the stream to send the username and password over in a HTTP basic authentication context
            $stream = stream_context_create(array(
                'http'  =>  array('header' => 'Authorization: Basic' . base64_encode($username . ':' . $password))
            ));

            // Connect and recieve the tweets, file_get_contents is clever at using sockets to read info!
            $tweets = file_get_contents($this->twitterUrl . $username, false, $context);
        } else {
            // Connect and recieve the tweets, file_get_contents is clever at using sockets to read info!
            $tweets = file_get_contents($this->twitterUrl . $username, false);
        }
        
        // Check the response headers from Twitter
        if($http_response_header[0] == 'HTTP/1.1 200 OK') {
            
            // Tweets received OK, store them in the cache
            $cacheError = $this->writeCache($username, $tweets);
            
            // Check if any errors happened during the caching
            if(empty($cacheError)) {
                
                // Return the cached Tweets
                return $this->readCache($username);
            
            } else {
                
                // Return the cache error
                return $cacheError;
            }
            
        } elseif($http_response_header[0] == 'HTTP/1.1 401 Unauthorized') {
            
            // The provided password was wrong, or a protected account was accessed without a password
            return 'Error: Your Twitter password is invalid or missing for accessing a protected account.';

        } elseif($http_response_header[0] == 'HTTP/1.1 404 Not Found') {
            
            // Twitter username could not be found
            return 'Error: The Twitter username provided could not be found.';
        
        } else {
            
            // Assume this is one of the "RARE" occassions Twitter is having problems
            return 'Error: Twitter is not available at this time.';
            
        }

    }
    
    /**
     * Read the current cache for a Twitter username
     *
     * @param string $username Twitter Username
     * @return array|string Array of tweets, or an error
     */
    private function readCache($username) {
        
        // Load the cache file using PHP's XML library
        $xml = simplexml_load_file($this->cacheFolder . $username . '.xml');
        
        // Check if the object is valid
        if(is_object($xml)) {
            
            // Store the tweets in an array
            $tweets = array();
            
            // Go through each tweet
            foreach($xml->status as $status) {
                $tweets[] = array(
                    'created'   =>  (string) $status->created_at,
                    'text'      =>  (string) $status->text
                );
            }
            
            // Return the maximum number of tweets
            return array_slice($tweets, 0, $this->maxTweets);
            
        } else {
            
            // The cache is invalid
            return 'Error: Cache is corrupted or has been modified and is no longer valid.';
        }
    }
    
    /**
     * Store the tweets fetched from Twitter in the cache folder
     *
     * @param string $username Twitter username
     * @param array $tweets Array of fetched Tweets
     * @return string Any errors, blank for none
     */
    private function writeCache($username, $tweets) {
        
        // Check the folder
        if(is_dir($this->cacheFolder)) {
            
            // Folder must be writable
            if(is_writable($this->cacheFolder)) {
                
                // Use the file_put_contents wrapper of fopen/fwrite/fclose for speed
                if(!file_put_contents($this->cacheFolder . $username . '.xml', $tweets)) {
                    
                    // An error occured if false was returned
                    return 'Error: The file could not be stored.';
                    
                } else {
                    
                    // Blank string if everything went OK
                    return '';
                    
                }
                
            } else {
                
                // Folder is not writable
                return 'Error: The cache folder is not writable.';
                
            }
            
        } else {
            
            // Cache folder does not exist
            return 'Error: The cache folder does not exist.';
            
        }
        
    }
    
}