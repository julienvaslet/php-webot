<?php

namespace html;

require_once( dirname( __FILE__ )."/HtmlElement.class.php" );

class HtmlText extends HtmlElement
{
    protected $text;

    public function __construct( string $text )
    {
        parent::__construct();
        $this->setText( $text );
    }

    public function getText()
    {
        return $this->text;
    }

    public function setText( string $text )
    {
        $this->text = $text;
    }

    public function toString()
    {
        return $this->text;
    }
    
    public function match( array $requirements )
    {
        return false;
    }
}

?>