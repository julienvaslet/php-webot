<?php

namespace html;

require_once( dirname( __FILE__ )."/HtmlElement.class.php" );

class HtmlTag extends HtmlElement
{
    protected $name;
    protected $attributes;

    public function __construct( string $name )
    {
        parent::__construct();
        $this->setName( $name );
        $this->attributes = array();
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName( string $name )
    {
        $this->name = strtolower( $name );
    }

    public function hasAttribute( string $attribute )
    {
        return array_key_exists( $attribute, $this->attributes );
    }

    public function getAttribute( string $attribute )
    {
        $value = null;

        if( $this->hasAttribute( $attribute ) )
            $value = $this->attributes[$attribute];
        
        else
            throw new \Exception( "Tag attribute not found." );
        
        return $value;
    }

    public function setAttribute( string $attribute, string $value )
    {
        $this->attributes[$attribute] = $value;
    }

    public function toString()
    {
        $indentation = 2;
        $closeInlineTags = true;

        $attributes = array();

        foreach( $this->attributes as $name => $value )
            array_push( $attributes, "{$name}=\"".addslashes( $value )."\"" );

        $content = "<{$this->name}";

        if( count( $attributes ) )
            $content .= " ".implode( " ", $attributes );

        if( count( $this->children ) == 0 && $closeInlineTags )
            $content .= "/>";
        else
            $content .= ">";
        
        if( count( $this->children ) > 0 )
        {
            $content .= "\n";

            foreach( $this->children as $child )
            {
                $childContent = $child->toString();
                $childContentLines = explode( "\n", $childContent );
                
                foreach( $childContentLines as &$line )
                    $line = str_repeat( " ", $indentation ).$line;
                
                $content .= implode( "\n", $childContentLines )."\n";
            }
        }

        if( !$closeInlineTags || count( $this->children ) > 0 )
            $content .= "</{$this->name}>";

        return $content;
    }
}

?>