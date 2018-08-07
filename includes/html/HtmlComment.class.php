<?php

namespace html;

require_once( dirname( __FILE__ )."/HtmlElement.class.php" );

class HtmlComment extends HtmlElement
{
    protected $comment;

    public function __construct( string $comment )
    {
        parent::__construct();
        $this->setComment( $comment );
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment( string $comment )
    {
        $this->comment = $comment;
    }

    public function toString()
    {
        return "<!-- {$this->comment} -->";
    }
}

?>