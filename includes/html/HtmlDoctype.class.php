<?php

namespace html;

require_once( dirname( __FILE__ )."/HtmlElement.class.php" );

class HtmlDoctype extends HtmlElement
{
    protected $doctype;

    public function __construct( string $doctype )
    {
        parent::__construct();
        $this->setDoctype( $doctype );
    }

    public function getDoctype()
    {
        return $this->doctype;
    }

    public function setDoctype( string $doctype )
    {
        $this->doctype = $doctype;
    }

    public function toString()
    {
        return "<!DOCTYPE {$this->doctype}>";
    }
}

?>