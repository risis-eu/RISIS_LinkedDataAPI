<?php

require_once 'lda.inc.php';
require_once 'data_handlers/data_handler.class.php';

class ItemDataHandler extends DataHandler{
    
    private $pageUri = false;
    private $endpointUrl = '';
    
    function __construct($Request, $ConfigGraph, $DataGraph, $Viewer, $SparqlWriter, $SparqlEndpoint, $endpointUrl) {
        parent::__construct($Request, $ConfigGraph, $DataGraph, $Viewer, $SparqlWriter, $SparqlEndpoint);
        $this->endpointUrl = $endpointUrl;
    }
    
    function loadData(){
        $uri = $this->ConfigGraph->getCompletedItemTemplate();
        $this->list_of_item_uris = array($uri);
        
        $this->viewQuery  = $this->SparqlWriter->getViewQueryForUri($uri, $this->viewer);
        if (LOG_VIEW_QUERIES) {
            logViewQuery($this->Request, $this->viewQuery);
        }
        
        $response = $this->SparqlEndpoint->graph($this->viewQuery, PUELIA_RDF_ACCEPT_MIMES);
        $this->pageUri = $this->Request->getUriWithoutPageParam();
        if($response->is_success()){
            $rdf = $response->body;
            $this->DataGraph->add_rdf($rdf);

            if ($this->DataGraph->is_empty()){
                throw new EmptyResponseException("Data not found in the triple store");
            }

            #	    echo $uri;
            $this->DataGraph->add_resource_triple($this->pageUri, FOAF.'primaryTopic', $uri);
       
            $this->DataGraph->add_resource_triple($uri , FOAF.'isPrimaryTopicOf', $this->pageUri);
            $this->DataGraph->add_resource_triple($this->Request->getUri(), API.'definition', $this->endpointUrl);
        } else {
            logError("Endpoint returned {$response->status_code} {$response->body} View Query <<<{$this->viewQuery}>>> failed against {$this->SparqlEndpoint->uri}");
            throw new ErrorException("The SPARQL endpoint used by this URI configuration did not return a successful response.");
        }
    }
    
    function getPageUri(){
        return $this->pageUri;
    }
    
}

?>