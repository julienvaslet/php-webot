<?php

namespace html;

abstract class HtmlElement
{
    protected $parent;
    protected $children;

    public function __construct()
    {
        $this->parent = null;
        $this->children = array();
    }

    public function prepend( HtmlElement &$element )
    {
        if( !is_null( $element->parent ) )
            $element->parent->remove( $element );

        array_unshift( $this->children, $element );
        $element->parent = $this;

        return $this;
    }

    public function append( HtmlElement &$element )
    {
        if( !is_null( $element->parent ) )
            $element->parent->remove( $element );

        array_push( $this->children, $element );
        $element->parent = $this;

        return $this;
    }

    public function remove( HtmlElement &$element )
    {
        $index = array_search( $element, $this->children, true );

        if( $index !== false )
        {
            array_splice( $this->children, $index, 1 );
            $element->parent = null;
        }

        return $this;
    }

    abstract public function toString();
}

?>