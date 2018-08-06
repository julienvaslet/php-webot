<?php

namespace http;

class HttpCookie
{
    protected $name;
    protected $value;
    protected $path;
    protected $domain;
    protected $expires;
    protected $maxAge;
    protected $secure;
    protected $httpOnly;

    public function __construct( $name, $value )
    {
        $this->name = $name;
        $this->value = $value;
        $this->path = null;
        $this->domain = null;
        $this->expires = null;
        $this->maxAge = null;
        $this->secure = null;
        $this->httpOnly = null;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function toString()
    {
        return "{$this->getName()}={$this->getValue()}";
    }

    public static function parse( $cookieString )
    {
        $cookie = null;

        $name = "";
        $value = "";

        $equal = strpos( $cookieString, "=" );

        if( $equal !== false )
        {
            $name = substr( $cookieString, 0, $equal );
            $semicolon = strpos( $cookieString, ";", $equal );

            if( $semicolon !== false )
                $value = substr( $cookieString, $equal + 1, $semicolon - $equal - 1 );
            else
                $value = substr( $cookieString, $equal + 1 );

            $cookie = new HttpCookie( $name, $value );
        }
        else
            throw new \Exception( "Invalid cookie format." );

        return $cookie;
    }
}

?>