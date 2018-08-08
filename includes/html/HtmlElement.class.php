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

    public function parent()
    {
        return $this->parent;
    }

    public function getSiblings()
    {
        $siblings = array();

        if( !is_null( $this->parent ) )
        {
            foreach( $this->parent->children as $sibling )
                array_push( $siblings, $sibling );
        }

        return $siblings;
    }

    public function getNextSibling()
    {
        $sibling = null;
        $instanceFound = false;

        foreach( $this->getSiblings() as $s )
        {
            if( $instanceFound )
            {
                $sibling = $s;
                break;
            }

            if( $s === $this )
                $instanceFound = true;
            
        }

        return $sibling;
    }

    public function getPreviousSibling()
    {
        $sibling = null;
        $instanceFound = false;
        $previousSibling = null;

        foreach( $this->getSiblings() as $s )
        {
            if( $s === $this )
            {
                $instanceFound = true;
                break;
            }
            
            $previousSibling = $s;
        }

        if( $instanceFound )
            $sibling = $previousSibling;

        return $sibling;
    }

    abstract public function match( array $requirements );

    protected static function parsePattern( string $pattern )
    {
        $operators = array( ">", "+", "~" );
        $tagPattern = '/^([^#\.\[\s:]*)(?:#([^\.\[\s:]+))?(?:\.([^\[\s:]+))*(?:\[([^\]]+)\])?(?::([^\s]+))*$/';
        $attributePattern = '/^([^\s=~\|\*\^\$]+)(?:([\^\$~\|\*]?=)(.*))?$/';

        $elements = array();
        $parts = preg_split( "/\s+/", $pattern );

        // Parts analysis
        foreach( $parts as $part )
        {
            if( in_array( $part, $operators ) )
            {
                array_push( $elements, $part );
            }
            else if( preg_match( $tagPattern, $part, $matches ) )
            {
                $tag = array(
                    "name" => $matches[1],
                    "classes" => array(),
                    "attributes" => array(),
                    "pseudoClasses" => array()
                );

                // Identifier
                if( count( $matches ) > 2 && strlen( $matches[2] ) > 0 )
                    array_push( $tag["attributes"], array( "id", "=", $matches[2] ) );

                // Classes
                if( count( $matches ) > 3 && strlen( $matches[3] ) > 0 )
                    $tag["classes"] = explode( ".", $matches[3] );

                // Attributes
                if( count( $matches ) > 4 && strlen( $matches[4] ) > 0 )
                {
                    $attributes = explode( ",", $matches[4] );

                    foreach( $attributes as $attribute )
                    {
                        if( preg_match( $attributePattern, $attribute, $attrMatches ) )
                        {
                            $name = $attrMatches[1];
                            $operator = null;
                            $value = null;

                            if( count( $attrMatches ) > 2 )
                            {
                                $operator = $attrMatches[2];
                                $value = $attrMatches[3];
                            }

                            array_push( $tag["attributes"], array( $name, $operator, $value ) );    
                        }
                        else
                            throw new \Exception( "Invalid find pattern: attribute selectors is incorrect." );
                    }
                }

                // Pseudo-classes
                if( count( $matches ) > 5 && strlen( $matches[5] ) > 0 )
                    $tag["pseudoClasses"] = explode( ":", $matches[5] );
                
                array_push( $elements, $tag );
            }
            else
                throw new \Exception( "Invalid find pattern." );
        }

        // Operators analysis
        $requirements = array();

        for( $i = count( $elements ) - 1 ; $i >= 0 ; --$i )
        {
            if( is_array( $elements[$i] ) )
            {
                $requirement = $elements[$i];

                if( $i > 0 && !is_array( $elements[$i - 1] ) )
                {
                    --$i;

                    if( $i == 0 )
                        throw new \Exception( "Incorrect find pattern: ends with an operator." );

                    if( !is_array( $elements[$i - 1] ) )
                        throw new \Exception( "Incorrect find pattern: two operators follow." );
                    
                    $relativeRequirement = $elements[$i - 1];

                    // Immediate parent
                    if( $elements[$i] == ">" )
                    {
                        $requirement["parent"] = $relativeRequirement;
                        //--$i;
                        // Parent is kept as it help the search to be faster.
                    }

                    // Previous sibling
                    else if( $elements[$i] == "~" )
                    {
                        $requirement["precededBy"] = $relativeRequirement;
                        --$i;
                    }

                    // Immediate previous sibling
                    else if( $elements[$i] == "+" )
                    {
                        $requirement["immediatlyAfter"] = $relativeRequirement;
                        --$i;
                    }
                }
                
                array_unshift( $requirements, $requirement );
            }
            else
            {
                throw new \Exception( "Incorrect find pattern: begins with an operator." );
            }
        }

        return $requirements;
    }

    public function find( string $pattern )
    {
        return $this->_find( HtmlElement::parsePattern( $pattern ) );
    }

    protected function _find( array $pattern )
    {
        $element = null;

        if( count( $pattern ) > 0 )
        {
            foreach( $this->children as $child )
            {
                if( $child->match( $pattern[0] ) )
                {
                    $subpattern = $pattern;
                    array_shift( $subpattern );

                    $element = $child->_find( $subpattern );
                }
                else
                {
                    $element = $child->_find( $pattern );
                }

                if( !is_null( $element ) )
                    break;
            }
        }
        else
            $element = $this;
        
        return $element;
    }

    public function findAll( string $pattern )
    {
        return $this->_findAll( HtmlElement::parsePattern( $pattern ) );
    }

    protected function _findAll( array $pattern )
    {
        $elements = array();

        if( count( $pattern ) > 0 )
        {
            foreach( $this->children as $child )
            {
                if( $child->match( $pattern[0] ) )
                {
                    $subpattern = $pattern;
                    array_shift( $subpattern );

                    $elements = array_merge( $elements, $child->_findAll( $subpattern ) );
                }
                else
                {
                    $elements = array_merge( $elements, $child->_findAll( $pattern ) );
                }
            }
        }
        else
            array_push( $elements, $this );

        return $elements;
    }

    abstract public function toString();
}

?>