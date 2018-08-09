<?php

namespace http;

class HttpUrl
{
    protected $protocol;
    protected $server;
    protected $port;
    protected $location;
    protected $anchor;
    protected $query;

    public function __construct( $server, $location, $protocol = "http", $port = 80, array $query = array(), $anchor = "" )
    {
        $this->server = $server;
        $this->location = $location;
        $this->protocol = $protocol;
        $this->port = $port;
        $this->query = $query;
        $this->anchor = $anchor;
    }

    public function getProtocol()
    {
        return strtolower( $this->protocol );
    }

    public function isSecure()
    {
        $secure = false;

        if( $this->getProtocol() == "https" )
            $secure = true;

        return $secure;
    }

    public function getServer()
    {
        return $this->server;
    }

    public function getLocation()
    {
        $location = "/";

        if( strlen( $this->location ) > 0 )
            $location = $this->location;
        
        return $location;
    }

    public function getRequestLocation()
    {
        $location = $this->getLocation();
        $queryString = $this->getQueryString();
        $anchor = $this->getAnchor();

        if( strlen( $queryString ) > 0 )
            $location .= "?{$queryString}";
        
        if( strlen( $anchor ) > 0 )
            $location .= "#{$anchor}";

        return $location;
    }

    public function getPort()
    {
        $port = $this->port;

        if( is_null( $port ) )
        {
            if( $this->getProtocol() == "http" )
                $port = 80;

            else if( $this->getProtocol() == "https" )
                $port = 443;
        }

        return $port;
    }

    public function addQuery( $key, $value )
    {
        $this->query[$key] = $value;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getQueryString()
    {
        $elements = array();

        foreach( $this->query as $key => $value )
        {
            if( is_string( $key ) && strlen( $value ) )
                array_push( $elements, urlencode( $key )."=".urlencode( $value ) );
            else if( !is_string( $key ) )
                array_push( $elements, urlencode( $value ) );
            else
                array_push( $elements, urlencode( $key ) );
        }

        return implode( "&", $elements ); 
    }

    public function getAnchor()
    {
        return $this->anchor;
    }

    public function toString()
    {
        return "{$this->getProtocol()}://{$this->getServer()}:{$this->getPort()}{$this->getRequestLocation()}";
    }

    public static function parse( $url, HttpUrl $relative = null )
    {
        $parsedUrl = null;

        if( !is_null( $relative ) && !preg_match( '%^[^:]+://%m', $url ) )
        {
            if( preg_match( '%^/%', $url ) )
                $url = "{$relative->getProtocol()}://{$relative->getServer()}:{$relative->getPort()}{$url}";

            else
                $url = "{$relative->getProtocol()}://{$relative->getServer()}:{$relative->getPort()}".dirname( $relative->getLocation() )."/{$url}";
        }

        if( $url instanceof HttpUrl )
        {
            $parsedUrl = $url;
        }
        else
        {
            $result = preg_match( "%^(?:([^:]+)://)([^/\?\#:]+)(?::([0-9]+))?((?:/[^\?\#]*)?)(?:\?([^#]*))?(?:#(.*))?$%iu", $url, $matches );

            if( $result !== false )
            {
                $anchor = "";
                $query = array();

                if( count( $matches ) > 5 && strlen( $matches[5] ) )
                {
                    $elements = explode( "&", $matches[5] );

                    foreach( $elements as $element )
                    {
                        $parts = explode( "=", $element );
                        $key = urldecode( $parts[0] );
                        $value = "";

                        if( count( $parts ) > 1 )
                            $value = urldecode( $parts[1] );
                        
                        $query[$key] = $value;
                    }
                }

                $port = null;

                if( strlen( $matches[3] ) )
                    $port = intval( $matches[3] );
                
                if( count( $matches ) > 6 && strlen( $matches[6] ) )
                    $anchor = $matches[6];

                //new HttpUrl( $server, $location, $protocol, $port, array $query, $anchor )
                $parsedUrl = new HttpUrl( $matches[2], $matches[4], $matches[1], $port, $query, $anchor );
            }
            else
            {
                throw new \Exception( "Invalid URL format." );
            }
        }

        return $parsedUrl;
    }
}

?>