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

    public function getClasses()
    {
        $classes = array();

        if( $this->hasAttribute( "class" ) )
            $classes = preg_split( '/\s+/', $this->getAttribute( "class" ) );

        return $classes;
    }

    public function hasClass( string $class )
    {
        return in_array( $class, $this->getClasses() );
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
    
    public function match( array $requirements )
    {
        $matches = true;

        if( array_key_exists( "name", $requirements ) && strlen( $requirements["name"] ) > 0 )
        {
            if( $this->name != $requirements["name"] )
                $matches = false;
        }

        if( $matches && array_key_exists( "classes", $requirements ) && count( $requirements["classes"] ) )
        {
            foreach( $requirements["classes"] as $class )
            {
                if( !$this->hasClass( $class ) )
                {
                    $matches = false;
                    break;
                }
            }
        }

        if( $matches && array_key_exists( "attributes", $requirements ) && count( $requirements["attributes"] ) )
        {
            foreach( $requirements["attributes"] as $attributeRequirement )
            {
                $name = $attributeRequirement[0];
                $operator = $attributeRequirement[1];
                $value = $attributeRequirement[2];

                if( $this->hasAttribute( $name ) )
                {
                    // Strictly equality
                    if( $operator == "=" )
                    {
                        if( $this->getAttribute( $name ) != $value )
                        {
                            $matches = false;
                            break;
                        }
                    }

                    // Contains the word
                    else if( $operator == "~=" )
                    {
                        if( !preg_match( '/\b'.$value.'\b/', $this->getAttribute( $name ) ) )
                        {
                            $matches = false;
                            break;
                        }
                    }

                    // Contains the characters
                    else if( $operator == "*=" )
                    {
                        if( !preg_match( '/'.$value.'/', $this->getAttribute( $name ) ) )
                        {
                            $matches = false;
                            break;
                        }
                    }

                    // Begins with
                    else if( $operator == "^=" )
                    {
                        if( !preg_match( '/^'.$value.'/', $this->getAttribute( $name ) ) )
                        {
                            $matches = false;
                            break;
                        }
                    }

                    // Ends with
                    else if( $operator == "$=" )
                    {
                        if( !preg_match( '/'.$value.'$/', $this->getAttribute( $name ) ) )
                        {
                            $matches = false;
                            break;
                        }
                    }

                    // Equality of the first hyphen-separated list element
                    else if( $operator == "|=" )
                    {
                        if( !preg_match( '/^'.$value.'-/', $this->getAttribute( $name ) ) )
                        {
                            $matches = false;
                            break;
                        }
                    }
                    else if( !is_null( $operator ) )
                        throw new \Exception( "Unknown attribute operator." );
                }
                else
                {
                    $matches = false;
                    break;
                }
            }
        }

        if( $matches && array_key_exists( "pseudoClasses", $requirements ) && count( $requirements["pseudoClasses"] ) )
        {
            // To be implemented.
        }

        if( $matches && array_key_exists( "parent", $requirements ) && is_array( $requirements["parent"] ) )
        {
            if( is_null( $this->parent ) || !$this->parent->match( $requirements["parent"] ) )
                $matches = false;
        }
        
        if( $matches && array_key_exists( "precededBy", $requirements ) && is_array( $requirements["precededBy"] ) )
        {
            $matches = false;

            foreach( $this->getSiblings() as $sibling )
            {
                if( $sibling === $this )
                    break;
                
                if( $sibling->match( $requirements["precededBy"] ) )
                {
                    $matches = true;
                    break;
                }
            }
        }

        if( $matches && array_key_exists( "immediatlyAfter", $requirements ) && is_array( $requirements["immediatlyAfter"] ) )
        {
            $previousSibling = $this->getPreviousSibling();

            if( is_null( $previousSibling ) || !$previousSibling->match( $requirements["immediatlyAfter"] ) )
                $matches = false;
        }

        return $matches;
    }
}

?>