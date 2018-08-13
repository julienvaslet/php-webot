<?php

require_once( dirname( __FILE__ )."/config.php" );

$includesDirectory = dirname( __FILE__ )."/../includes";

require_once( $includesDirectory."/http/HttpClient.class.php" );
require_once( $includesDirectory."/http/HttpUrl.class.php" );
require_once( $includesDirectory."/html/HtmlParser.class.php" );
use http\HttpClient;
use http\HttpUrl;
use html\HtmlParser;

function info( string $message, array $sections = array() )
{
    array_unshift( $sections, date( "Y-m-d H:i:s" ) );
    echo "[".implode( "][", $sections )."] ".$message."\n";
}

function warn( string $message )
{
    info( $message, array( "WARNING" ) );
}

function error( string $message )
{
    info( $message, array( "ERROR" ) );
}

$steps = array(
    "home" => array(
        "url" => "/fr/particuliers.html",
        "data" => null,
        "process" => null
    ),
    "login" => array(
        "url" => "/fr/authentification.html",
        "data" => array(
            "_cm_user" => $configuration["username"],
            "flag" => "password",
            "_cm_pwd" => $configuration["password"],
            "submit.x" => 0,
            "submit.y" => 0
        ),
        "process" => function( $root, $response, $httpClient )
        {
            $logoutButton = $root->find( "a#ei_tpl_ident_logout_title" );

            if( !is_null( $logoutButton ) )
            {
                info( "Connected." );
                return true;
            }
            else
            {
                error( "Unable to log in." );
                return false;
            }
        }
    ),
    "export" => array(
        "url" => "/fr/banque/compte/telechargement.cgi",
        "data" => null,
        "process" => function( $root, $response, $httpClient )
        {
            global $configuration;
            $success = true;

            $exportData = array(
                "data_formats_selected" => "csv",
                "data_formats_options_cmi_download" => 0,
                "data_formats_options_ofx_format" => 7,
                "Bool:data_formats_options_ofx_zonetiers" => "false",
                "CB:data_formats_options_ofx_zonetiers" => "on",
                "data_formats_options_qif_fileformat" => 6,
                "data_formats_options_qif_dateformat" => 0,
                "data_formats_options_qif_amountformat" => 0,
                "data_formats_options_qif_headerformat" => 0,
                "Bool:data_formats_options_qif_zonetiers" => "false",
                "CB:data_formats_options_qif_zonetiers" => "on",
                "data_formats_options_csv_fileformat" => 2,
                "data_formats_options_csv_dateformat" => 0,
                "data_formats_options_csv_fieldseparator" => 1,
                "data_formats_options_csv_amountcolnumber" => 0,
                "data_formats_options_csv_decimalseparator" => 1,
                "data_daterange_value" => "all",
                "_FID_DoDownload.x" => 0,
                "_FID_DoDownload.y" => 0,
                "data_formats_options_cmi_show" => "True",
                "data_formats_options_qif_show" => "True",
                "data_formats_options_excel_show" => "True",
                "data_formats_options_excel_selected%5fformat" => "xl-2007",
                "data_formats_options_csv_show" => "True"
            );
            
            $form = $root->find( "form[id=P:F]" );
            $exportUrl = null;

            if( !is_null( $form ) )
            {
                $exportUrl = HttpUrl::parse( $form->getAttribute( "action" ), $response->getUrl() );
                $exportUrl = $exportUrl->toString();
            }

            if( !is_null( $exportUrl ) && strlen( $exportUrl ) )
            {
                $accountTable = $root->find( "table#account-table" );

                if( !is_null( $accountTable ) )
                {
                    $trs = $accountTable->findAll( "tr" );
                    $i = 0;

                    foreach( $trs as $tr )
                    {
                        $label = $tr->find( "label" );
                        
                        if( !is_null( $label ) )
                        {
                            $importAccount = true;

                            if( !is_null( $configuration["accountExclusion"] ) )
                            {
                                if( preg_match( $configuration["accountExclusion"], $label->getText() ) )
                                    $importAccount = false;
                            }

                            if( $importAccount )
                            {
                                info( "Gathering transactions from: ".$label->getText() );

                                $data = $exportData;
                                $data["data_accounts_selection"] = "";

                                for( $j = 0 ; $j < count( $trs ) ; ++$j )
                                {
                                    $name = ( $j == 0 ) ? "data_accounts_account_ischecked" : "data_accounts_account_".($j + 1)."__ischecked";
                                    
                                    if( $j == $i )
                                    {
                                        $data["Bool:".$name] = "true";
                                        $data["CB:".$name] = "on";
                                        $data["data_accounts_selection"] .= "1";
                                    }
                                    else
                                    {
                                        $data["Bool:".$name] = "false";
                                        $data["data_accounts_selection"] .= "0";
                                    }
                                }
                                
                                $httpClient->pushHistory();
                                $postResponse = $httpClient->post( $exportUrl, $data );
                                $httpClient->popHistory();

                                if( $postResponse->getHttpCode() == 200 )
                                {
                                    if( is_callable( $configuration["import"] ) )
                                    {
                                        // Cleaning carriage return sequence and delete header line
                                        $contentLines = preg_split( "/\r?\n/", $postResponse->getContent() );
                                        $contentLines = array_filter( $contentLines, function( $index ) { return $index > 0; }, ARRAY_FILTER_USE_KEY );
                                        $content = implode( "\n", $contentLines );

                                        if( !$configuration["import"]( $label->getText(), $content ) )
                                        {
                                            error( "Import failed." );
                                            $status = false;
                                        }
                                    }
                                    else
                                    {
                                        error( "No import function defined." );
                                        $status = false;
                                        break;
                                    }
                                }
                                else
                                {
                                    error( "Unable to export data account." );
                                    $status = false;
                                }
                            }
                        }

                        ++$i;
                    }
                }
                else
                {
                    error( "Unable to find table#account-table: structure has changed?" );
                    $success = false;
                }
            }
            else
            {
                error( "Unable to find form URL." );
                $success = false;
            }

            return $success;
        }
    ),
    "logout" => array(
        "url" => "/fr/deconnexion/deconnexion.cgi",
        "data" => null,
        "process" => function( $root, $response, $httpClient )
        {
            $logoutButton = $root->find( "a#ei_tpl_ident_logout_title" );

            if( is_null( $logoutButton ) )
                info( "Disconnected." );

            else
                warn( "Unable to log out." );

            return true;
        }
    )
);

$stepsOrder = array( "home", "login", "export", "logout" );

info( "Starting CMLACO import." );

$httpClient = new HttpClient();
//$httpClient->activateDebug();

$baseUrl = HttpUrl::parse( "https://www.creditmutuel.fr/" );

foreach( $stepsOrder as $stepName )
{
    if( array_key_exists( $stepName, $steps ) )
    {
        $step = $steps[$stepName];
        $url = HttpUrl::parse( $step["url"], $baseUrl );
        $response = null;

        info( "Processing ".$url->toString() );

        // Post
        if( array_key_exists( "data", $step ) && is_array( $step["data"] ) && count( $step["data"] ) > 0 )
            $response = $httpClient->post( $url->toString(), $step["data"] );

        // Get
        else
            $response = $httpClient->get( $url->toString() );
        
        if( $response->getHttpCode() == 200 )
        {
            if( array_key_exists( "process", $step ) && is_callable( $step["process"] ) )
            {
                $parser = new HtmlParser( $response->getContent() );
                $root = $parser->parse();

                if( !$step["process"]( $root, $response, $httpClient ) )
                {
                    error( "Page processing failed." );
                    break;
                }
            }
        }
        else
        {
            error( "HTTP error: ".$response->getHttpCode()." ".$response->getHttpMessage() );
            break;
        }
    }
    else
    {
        error( "Step \"".$stepName."\" does not exists." );
        break;
    }
}

info( "End of import." );

?>