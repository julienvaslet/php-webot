<?php

namespace http;

class HttpResponse
{
    protected $httpCode;
    protected $httpMessage;
    protected $headers;
    protected $content;
    
    public function __construct( $httpCode, $httpMessage, $content, array $headers = array() )
    {
        $this->httpCode = $httpCode;
        $this->httpMessage = $httpMessage;
        $this->content = $content;
        $this->headers = $headers;
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
}

?>