<?php

$includesDirectory = dirname( __FILE__ )."/../includes";

require_once( $includesDirectory."/http/HttpClient.class.php" );
require_once( $includesDirectory."/http/HttpUrl.class.php" );
require_once( $includesDirectory."/html/HtmlParser.class.php" );
use http\HttpClient;
use http\HttpUrl;
use html\HtmlParser;

$httpClient = new HttpClient();
$processedUrls = array();

function processUrl( $url )
{
    global $httpClient, $processedUrls;

    if( !in_array( $url, $processedUrls ) )
    {
        echo "Processing {$url}...\n";
        array_push( $processedUrls, $url );
        $response = $httpClient->get( $url );

        $parser = new HtmlParser( $response->getContent() );
        $root = $parser->parse();

        $tags = $root->findAll( "a[href]" );

        foreach( $tags as $tag )
        {
            $childUrl = HttpUrl::parse( $tag->getAttribute( "href" ), $response->getUrl() );

            if( $childUrl->getServer() == $response->getUrl()->getServer() )
            {
                $httpClient->pushHistory();
                processUrl( $childUrl->toString() );
                $httpClient->popHistory();
            }
        }
    }
}

processUrl( "http://labosauvage.free.fr/" );

echo "\nSummary:\n--------\n";

foreach( $processedUrls as $url )
    echo $url."\n";

echo "\n".count( $processedUrls )." urls processed.";

?>