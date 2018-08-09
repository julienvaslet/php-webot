<?php

namespace http;

class HttpResponse
{
    protected $url;
    protected $httpCode;
    protected $httpMessage;
    protected $headers;
    protected $content;
    
    public function __construct( $url, $httpCode, $httpMessage, $content, array $headers = array() )
    {
        $this->url = $url;
        $this->httpCode = $httpCode;
        $this->httpMessage = $httpMessage;
        $this->content = $content;
        $this->headers = $headers;
    }

    public function getUrl()
    {
        return $this->url;
    }
    
    public function getHttpCode()
    {
        return $this->httpCode;
    }
    
    public function getHttpResponse()
    {
        return $this->httpResponse;
    }
    
    public function getHeaders()
    {
        return $this->headers;
    }

    public function getContent()
    {
        return $this->content;
    }
}

?>