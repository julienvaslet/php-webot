<?php

namespace http;

require_once( dirname( __FILE__ )."/HttpRequest.class.php" );
require_once( dirname( __FILE__ )."/HttpResponse.class.php" );
require_once( dirname( __FILE__ )."/HttpCookie.class.php" );

class HttpClient
{
    protected $historyStack;
    protected $history;
    protected $cookies;
    protected $debug;
    
    public function __construct()
    {
        $this->historyStack = array();
        $this->history = array();
        $this->cookies = array();
        $this->debug = false;
    }

    public function activateDebug()
    {
        $this->debug = true;
    }

    public function deactivateDebug()
    {
        $this->debug = false;
    }

    public function pushHistory()
    {
        $history = array(
            "history" => $this->history,
            "cookies" => $this->cookies
        );

        array_push( $this->historyStack, $history );
    }

    public function popHistory()
    {
        if( count( $this->historyStack ) > 0 )
        {
            $history = array_pop( $this->historyStack );
            $this->history = $history["history"];
            $this->cookies = $history["cookies"];
        }
    }

    protected function processRequest( $request )
    {
        if( count( $this->history ) > 0 )
        {
            $lastUrl = $this->history[0];
            
            if( $lastUrl->getServer() == $request->getUrl()->getServer() )
                $lastUrl = $lastUrl->getRequestLocation();

            else
                $lastUrl = $lastUrl->toString();

            $request->setHeader( "Referer", $lastUrl );
        }

        if( count( $this->cookies ) > 0 )
        {
            $cookies = array();

            foreach( $this->cookies as $cookie )
                array_push( $cookies, $cookie->toString() );
            
            $request->setHeader( "Cookie", implode( "; ", $cookies ) );
        }
        
        $remainingRedirections = 10;

        do
        {
            if( $this->debug )
            {
                echo "------------------------------------\n";
                echo date( "Y-m-d H:i:s" )."\n";
                echo "------------------------------------\n";
                echo $request->toString();
                echo "------------------------------------\n";
            }

            $response = $request->process();

            if( $this->debug )
            {
                echo "------------------------------------\n";
                echo date( "Y-m-d H:i:s" )."\n";
                echo "------------------------------------\n";
                echo $response->toString();
                echo "------------------------------------\n";
                echo "Read content length: ".strlen( $response->getContent() )."\n";
                echo "------------------------------------\n";
            }

            if( $response->hasHeader( "Set-Cookie" ) )
            {
                $setCookies = $response->getHeader( "Set-Cookie" );

                if( !is_array( $setCookies ) )
                    $setCookies = array( $setCookies );
                
                foreach( $setCookies as $setCookie )
                {
                    $cookie = HttpCookie::parse( $setCookie );
                    $this->cookies[$cookie->getName()] = $cookie;
                }
            }

            if( in_array( $response->getHttpCode(), array( 301, 302 ) ) )
            {
                if( array_key_exists( "Location", $response->getHeaders() ) )
                {
                    $request->setMethod( "GET" );
                    $request->resetContent();
                    $request->setUrl( $response->getHeaders()["Location"] );
                    $request->setHeader( "Referer", $response->getUrl()->toString() );
                    $response = null;
                    $remainingRedirections--;
                }
                else
                    throw new \Exception( "Asking for a redirection, but no location has been provided." );
            }
        }
        while( is_null( $response ) && $remainingRedirections > 0 );

        if( $remainingRedirections == 0 )
            throw new \Exception( "Maximum of ten (10) redirections exceeded." );

        if( $response->getHttpCode() == 200 )
        {
            // Save the URL in the history
            array_unshift( $this->history, $request->getUrl() );
        }

        return $response;
    }

    public function get( $url, array $get = array() )
    {
        $request = new HttpGetRequest( $url );

        foreach( $get as $key => $value )
            $request->getUrl()->addQuery( $key, $value );
        
        return $this->processRequest( $request );
    }
    
    public function post( $url, array $post = array(), array $get = array() )
    {
        $request = new HttpPostRequest( $url );

        foreach( $get as $key => $value )
            $request->getUrl()->addQuery( $key, $value );

        $request->setHeader( "Content-Type", "application/x-www-form-urlencoded" );

        $postElements = array();
        foreach( $post as $key => $value )
        {
            if( is_string( $key ) && strlen( $value ) )
                array_push( $postElements, urlencode( $key )."=".urlencode( $value ) );

            else if( !is_string( $key ) )
                array_push( $postElements, urlencode( $value ) );
            
            else
                array_push( $postElements, urlencode( $key ) );
        }

        $request->setContent( implode( "&", $postElements ) );
        
        return $this->processRequest( $request );
    }
}

?>