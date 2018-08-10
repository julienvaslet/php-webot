<?php

namespace http;

class HttpResponse
{
    protected $url;
    protected $httpVersion;
    protected $httpCode;
    protected $httpMessage;
    protected $headers;
    protected $content;
    
    public function __construct( $url, $httpVersion, $httpCode, $httpMessage, $content, array $headers = array() )
    {
        $this->url = $url;
        $this->httpVersion = $httpVersion;
        $this->httpCode = $httpCode;
        $this->httpMessage = $httpMessage;
        $this->content = $content;
        $this->headers = $headers;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getHttpVersion()
    {
        return $this->httpVersion;
    }
    
    public function getHttpCode()
    {
        return $this->httpCode;
    }
    
    public function getHttpResponse()
    {
        return $this->httpResponse;
    }

    public function hasHeader( $header )
    {
        return array_key_exists( $header, $this->headers );
    }

    public function getHeader( $header )
    {
        $value = null;

        if( $this->hasHeader( $header ) )
            $value = $this->headers[$header];

        return $value;
    }
    
    public function getHeaders()
    {
        return $this->headers;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function toString()
    {
        $content = "HTTP/{$this->httpVersion} {$this->httpCode} {$this->httpMessage}\r\n";

        foreach( $this->headers as $header => $value )
        {
            if( is_array( $value ) )
            {
                foreach( $value as $v )
                    $content .= "{$header}: {$v}\r\n";
            }
            else
                $content .= "{$header}: {$value}\r\n";
        }

        return $content;
    }
}

?>