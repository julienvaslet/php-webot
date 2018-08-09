<?php

namespace http;

require_once( dirname( __FILE__ )."/HttpUrl.class.php" );
require_once( dirname( __FILE__ )."/HttpResponse.class.php" );

class HttpRequest
{
    protected $method;
    protected $url;
    protected $headers;
    protected $content;

    public function __construct( $method, $url )
    {
        $this->method = $method;
        $this->setUrl( $url );
        $this->content = null;
        $this->headers = array(
            "Accept-Encoding" => "deflate",
            "Accept-Charset" => "utf-8",
            "Connection" => "Close"
        );
    }

    public function getMethod()
    {
        return strtoupper( $this->method );
    }

    public function setUrl( $url )
    {
        $this->url = HttpUrl::parse( $url );
    }

    public function &getUrl()
    {
        return $this->url;
    }

    public function setHeader( $key, $value )
    {
        $this->headers[$key] = $value;
    }

    public function setContent( $content )
    {
        $this->content = $content;
    }

    public function toString()
    {
        $request = "{$this->getMethod()} {$this->url->getRequestLocation()} HTTP/1.0\r\n";
        $request .= "Host: {$this->url->getServer()}\r\n";

        foreach( $this->headers as $header => $value )
        {
            $request .= "{$header}: {$value}\r\n";
        }

        if( !is_null( $this->content ) )
            $request .= "Content-Length: ".strlen( $this->content )."\r\n";

        $request .= "\r\n";

        if( !is_null( $this->content ) )
            $request .= $this->content;

        return $request;
    }

    public function process()
    {
        $httpCode = 0;
        $httpMessage = "";
        $content = "";
        $headers = array();

        $hostname = $this->url->getServer();

        if( $this->url->isSecure() )
            $hostname = "ssl://{$hostname}";

        $socket = fsockopen( $hostname, $this->url->getPort(), $errno, $errstr );

        if( $socket !== false )
        {
            $responseHeaders = "";

            // Send request
            fwrite( $socket, $this->toString() );

            // Reading headers
            while( !feof( $socket ) && substr( $responseHeaders, -4 ) != "\r\n\r\n" )
                $responseHeaders .= fread( $socket, 1 );

            $responseHeaders = explode( "\r\n", $responseHeaders );
            
            if( count( $responseHeaders ) )
            {
                $statusLine = array_shift( $responseHeaders );
                $result = preg_match( "%^HTTP/([^\s]+) ([0-9]+) (.*)$%", $statusLine, $matches );

                if( $result )
                {
                    $httpVersion = $matches[1];
                    $httpCode = intval( $matches[2] );
                    $httpMessage = $matches[3];

                    foreach( $responseHeaders as $header )
                    {
                        $colon = strpos( $header, ":" );

                        if( $colon !== false )
                        {
                            $key = substr( $header, 0, $colon );
                            $value = ltrim( substr( $header, $colon + 1 ) );

                            if( array_key_exists( $key, $headers ) )
                            {
                                if( !is_array( $headers[$key] ) )
                                    $headers[$key] = array( $headers[$key] );

                                array_push( $headers[$key], $value );
                            }
                            else
                                $headers[$key] = $value;
                        }
                    }
                }
                else
                    throw new \Exception( "Unable to read HTTP status of the response." );

            }
            else
                throw new \Exception( "Unable to parse HTTP headers from server response." );
            
            if( $httpCode != 0 )
            {
                $contentLength = 0;

                if( array_key_exists( "Content-Length", $headers ) && preg_match( "%^[0-9]+$%", $headers["Content-Length"] ) )
                    $contentLength = intval( $headers["Content-Length"] );
                
                if( $contentLength > 0 )
                {
                    $content = "";
                    $bufferLength = 1024;
                    $readLength = 0;

                    while( $readLength < $contentLength )
                    {
                        if( $contentLength - $readLength < $bufferLength )
                            $bufferLength = $contentLength - $readLength;

                        $content .= fread( $socket, $bufferLength );
                        $readLength += $bufferLength;
                    }
                }

                else
                {
                    while( !feof( $socket ) )
                        $content .= fread( $socket, 1 );
                }
            }

            fclose( $socket );
        }
        else
            throw new \Exception( "Unable to open socket: ({$errno}) {$errstr}" );
        
        return new HttpResponse( $this->url, $httpVersion, $httpCode, $httpMessage, $content, $headers );
    }
}

class HttpGetRequest extends HttpRequest
{
    public function __construct( $url )
    {
        parent::__construct( "GET", $url );
    }
}

class HttpPostRequest extends HttpRequest
{
    public function __construct( $url )
    {
        parent::__construct( "POST", $url );
    }
}

?>