<?php

namespace html;

require_once( dirname( __FILE__ )."/HtmlDoctype.class.php" );
require_once( dirname( __FILE__ )."/HtmlComment.class.php" );
require_once( dirname( __FILE__ )."/HtmlTag.class.php" );
require_once( dirname( __FILE__ )."/HtmlText.class.php" );

class HtmlParser
{
    protected $html;

    public function __construct( string $html )
    {
        $this->html = $html;
    }

    public function parse()
    {
        $root = new HtmlTag( "root" );
        $tags = array();

        $pattern = '%(?:<!--.*?-->|<![^>]+>|<\s*/?\s*[^>\s]+(?:\s+[^=>\s]+(?:\s*=\s*"(?:[^"]|\\\\")*")?)*\s*/?\s*>)%s';
        $doctypePattern = '%<!DOCTYPE\s+([^>]+)>%s';
        $commentPattern = '%<!--\s*(.*?)\s*-->%s';
        $tagPattern = '%<\s*(/?)\s*([^>\s]+)((?:\s+[^=\s/]+(?:\s*=\s*"(?:[^"]|\\\\")*")?)*)\s*(/?)\s*>%s';
        $attributePattern = '%([^=\s]+)(?:\s*=\s*"((?:[^"]|\\\\")*)")?%s';
        $rawContentTags = array( "script", "pre", "code" );
        
        preg_match_all( $pattern, $this->html, $matches, PREG_OFFSET_CAPTURE );

        $lastOffset = 0;
        $iMatch = 0;

        for( $iMatch = 0 ; $iMatch < count( $matches[0] ) ; ++$iMatch )
        {
            $capture = $matches[0][$iMatch];

            // Handle text content
            $text = trim( substr( $this->html, $lastOffset, $capture[1] - $lastOffset ) );
            
            if( strlen( $text ) > 0 )
            {
                $textNode = new HtmlText( $text );
                $root->append( $textNode );
                array_push( $tags, array( "open", $textNode ) );
            }
                
            $lastOffset = $capture[1] + strlen( $capture[0] );

            // Handle tags, doctype and comments
            if( preg_match( $doctypePattern, $capture[0], $doctypeMatch ) )
            {
                $doctype = new HtmlDoctype( $doctypeMatch[1] );
                $root->append( $doctype );

                array_push( $tags, array( "open", $doctype ) );
            }
            else if( preg_match( $commentPattern, $capture[0], $commentMatch ) )
            {
                $comment = new HtmlComment( $commentMatch[1] );
                $root->append( $comment );
                
                array_push( $tags, array( "open", $comment ) );
            }
            else if( preg_match( $tagPattern, $capture[0], $tagMatch ) )
            {
                $selfClosed = strlen( $tagMatch[4] ) > 0;
                $close = strlen( $tagMatch[1] ) > 0;
                $name = strtolower( $tagMatch[2] );

                if( !$close )
                {
                    $tag = new HtmlTag( $name );
                    preg_match_all( $attributePattern, trim($tagMatch[3]), $attributeMatch );
                    
                    for( $i = 0 ; $i < count( $attributeMatch[0] ) ; $i++ )
                    {
                        $value = htmlspecialchars_decode( stripslashes( $attributeMatch[2][$i] ) );

                        if( strlen( $value ) == 0 )
                            $value = "true";

                        $tag->setAttribute( strtolower( $attributeMatch[1][$i] ), $value );
                    }

                    $root->append( $tag );
                    array_push( $tags, array( "open", $tag ) );

                    // Raw content tags, jump to closing tag
                    if( in_array( $tag->getName(), $rawContentTags ) && !$selfClosed )
                    {
                        $jMatch = $iMatch + 1;

                        for( ; $jMatch < count( $matches[0] ) ; ++$jMatch )
                        {
                            $subcapture = $matches[0][$jMatch];

                            if( preg_match( $tagPattern, $subcapture[0], $subtagMatch ) )
                            {
                                $subName = strtolower( $subtagMatch[2] );
                                $subClose = strlen( $subtagMatch[1] ) > 0;

                                if( $subName == $tag->getName() && $subClose )
                                    break;
                            }
                        }

                        // Ignoring not found closing tag and set the next tag to handle
                        // to the closing one. Content will automatically be added (warn: trimed).
                        if( $jMatch < count( $matches[0] ) )
                            $iMatch = $jMatch - 1;
                    }
                }
                else
                {
                    $stackedTags = 0;

                    for( $tagIndex = count( $tags ) - 1 ; $tagIndex >= 0 ; --$tagIndex )
                    {
                        if( $tags[$tagIndex][0] == "close" && $tags[$tags[$tagIndex][1]][1]->getName() == $name )
                            ++$stackedTags;

                        if( $tags[$tagIndex][0] == "open" && $tags[$tagIndex][1] instanceof HtmlTag && $tags[$tagIndex][1]->getName() == $name )
                        {
                            if( $stackedTags == 0 )
                                break;
                            else
                                --$stackedTags;
                        }
                    }

                    if( $tagIndex >= 0 )
                        array_push( $tags, array( "close", $tagIndex ) );

                    /*else
                        echo "Ignoring closing tag {$name} because opening tag was not found.\n";*/
                }
            }
        }

        // Handle last text content
        $text = trim( substr( $this->html, $lastOffset, $capture[1] - $lastOffset ) );
        
        if( strlen( $text ) > 0 )
        {
            $textNode = new HtmlText( $text );
            $root->append( $textNode );
            array_push( $tags, array( "open", $textNode ) );
        }

        // Closing tags
        for( $i = count( $tags ) - 1 ; $i >= 0 ; --$i )
        {
            if( $tags[$i][0] == "close" )
            {
                $openTagIndex = $tags[$i][1];
                $matchingTag = $tags[$openTagIndex][1];

                // Append tags while it is not the matching open.
                for( $j = $i - 1 ; $j > $tags[$i][1] ; --$j )
                {
                    if( $tags[$j][0] == "open" )
                        $matchingTag->prepend( $tags[$j][1] );
                }
            }
        }
 
        return $root;
    }
}

?>