<?php
namespace LD\APIBundle\Services\Factories;

use LD\APIBundle\Entity\Region;
use LD\APIBundle\Entity\Theme;
use LD\APIBundle\Entity\Country;
use LD\APIBundle\Entity\EmptyEntity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use \Iterator;

/**
 * Default entoty factory
 */
class GetFactory extends BaseFactory
{
    protected $data;

    /**
     * Take an EasyRDF response and process it into data ready to be used later.
     *
     * @param mixed  $data  The response from the sparql query
     * @param string $type  The type of the data object being processed
     * @param string $graph The name of the graph that was used in this query
     *
     * @return array
     */
    public function process($data, $type, $graph = 'all')
    {
        $type = 'documents'; // <-- ISSUE: $type does not appear to be being passed in
        
        $func = 'get' . ucfirst($type);

        $this->data = call_user_func_array(
            array($this, $func),
            array($data, $graph, $type)
        );
        /* Debug code for outputing the EasyRDF object
        return $this->data = array(
            'object_id' => $type . ' ' . $graph,
            'object_name' => 'Unkown object',
            'metadata_url' => $this->getContainer()->get('router')->generate('ld_api_api_index'),
            'data' => print_r($data, true),
        );*/
    }

    /**
     * Parse the results and build the response data array
     *
     * @param mixed  $data  An EasyRDF object containing the results of a construct query
     * @param string $graph The name of the graph it use.
     *
     * @return array
     */
    
    function getDocuments($data, $graph) {
        
        $data = $data['default'];
        
        if($data->isEmpty()) {
            return array();
        }
        
        foreach($data->allOfType("bibo:Article") as $resource) {
            $resource_id = $resource->get("dcterms:identifier")->getValue();
            if($resource_id == $resource->getUri()) {
                //We are dealing with a resource_id which is the URI. We need to make a best guess at the graph and ID. 
                //We grab graph from just after the domain, and ID from the end of the URL.
                $resource_url = explode('/',parse_url($resource_id,PHP_URL_PATH));
                $resource_graph = $resource_url[1];
                $resource_id = $resource_url[count($resource_url)-2];
            } else {
                $resource_graph = $graph;
            }
            
            //Note - we currently don't implement category_subject as this data is not captured in the R4D RDF or in the data coming from ELDIS. 
            
            $document = array();
            //We add this custom property not originally in the IDS API
            $document['linked_data_uri'] = $resource->getUri(); 
            
            foreach($resource->all("dcterms:creator") as $author) {
                $document['author'][] = $author->getValue();
            }
            
            foreach($resource->all("dcterms:coverage") as $coverage) {
                if($coverage->hasProperty("http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeISO2")) {
                    $document['country_focus_array']->Country[] = array("alternative_name"=>$coverage->get("rdfs:label")->getValue(),
                                                                          "iso_two_letter_code"=>$coverage->get("<http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeISO2>")->getValue(),
                                                                          "metadata_url"=> $this->getContainer()->get('router')->generate('ld_api_api_index').$graph."/get/countries/".$coverage->get("<http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeISO2>")->getValue()."/full",
                                                                          "object_id"=>$coverage->get("<http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeISO2>")->getValue(),
                                                                          "object_name"=>$coverage->get("rdfs:label")->getValue(),
                                                                          "object_type"=>"Country");
                  $document['country_focus'][] = $coverage->get("rdfs:label")->getValue();
                  $document['country_focus_ids'][] = $coverage->get("<http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeISO2>")->getValue();
                                    
                } else {
                    //If we have the UN identifier we use that
                    $coverageID = $coverage->hasProperty("http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeUN") ? "UN".$coverage->get("<http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeUN>")->getValue() : $coverage->get("dcterms:identifier")->getValue();                                   
                    $document['category_region_array']->Region[] = array("archived"=>"false",
                                                                          "deleted"=>"0",
                                                                          "metadata_url"=> $this->getContainer()->get('router')->generate('ld_api_api_index').$graph."/get/regions/".$coverageID."/full",
                                                                          "object_id"=>$coverageID,
                                                                          "object_name"=>$coverage->get("rdfs:label")->getValue(),
                                                                          "object_type"=>"region");
                  $document['category_region_path'][] = $coverage->get("rdfs:label")->getValue();
                  $document['category_region_ids'][] = $coverageID;
                  $document['category_region_objects'][] = $coverageID ."|region|".$coverage->get("rdfs:label")->getValue();
                }        
            }
            
            //EasyRDF is currently not getting all the subjects as it should. See https://github.com/practicalparticipation/ldapi/issues/4
            foreach($resource->all("dcterms:subject") as $theme) {
                if($theme->hasProperty("dcterms:identifier")) {
                    $themeID = $theme->get("dcterms:identifier")->getValue();
                } else {
                    //We are probably dealing with an Agrovoc or dbpedia theme, so just get the last part of the URL
                    $themeID = str_replace("/","",strrchr($theme->getURI(),"/"));
                }
                
                
                $document['category_theme_array']->theme[] = array("archived"=>"false",
                                                                    "level" => 'unknown',
                                                                    "metadata_url" => $this->getContainer()->get('router')->generate('ld_api_api_index').$graph."/get/themes/".$themeID,
                                                                    "object_id" => $themeID,
                                                                    "object_name" => $theme->get("rdfs:label")->getValue(),
                                                                    "object_type"=>"theme");
                $document['category_theme_ids'][] = $themeID;
                
                //When easy RDF is correctly fetching then we can use skos:narrower properties to output category theme paths here. 
            }
            

            $document['metadata_url'] = $this->getContainer()->get('router')->generate('ld_api_api_index').$resource_graph."/get/documents/".$resource_id."/full";
            $document['license_type'] = "Not Known"; //ToDo - get license data into system
            $document['name'] = $resource->get("dcterms:title")->getValue();
            $document['object_id'] = $resource->get("dcterms:identifier")->getValue();
            $document['object_type'] = "Document";
            $document['publication_date'] = str_replace("T"," ",$resource->get("dcterms:date")->getValue());
            $document['publication_year'] = date("Y",strtotime($resource->get("dcterms:date")->getValue()));

            $document['publisher'] = $resource->get("dcterms:publisher/foaf:name") ? $resource->get("dcterms:publisher/foaf:name")->getValue() : null;
            
            //ToDo - Add more publisher details here (waiting for cache to clear)
            
            $document['site'] = explode("/",parse_url($resource->getUri())['path'])[1];
            
            $document['title'] = $resource->get("dcterms:title")->getValue();

            $document['urls'][] = $resource->get("<http://purl.org/ontology/bibo/uri>") ? $resource->get("<http://purl.org/ontology/bibo/uri>")->getURI() : null;
            
            $document['website_url'] = $resource->get("rdfs:seeAlso") ? $resource->get("rdfs:seeAlso")->getUri() : null;
            
            $return[] = $document;
        }
        
        return $return;
    
    }

    /**
     * Format the data held by this factory ready to be output by the API.
     *
     * @param integer $format The format to build a response for.
     *
     * @see FactoryInterface:SHORT
     * @see FactoryInterface:FULL
     *
     * @return array
     */
    public function getResponse($format)
    {
        switch ($format) {
            case self::FULL:
                return $this->data;
                break;
            default:
            case self::SHORT:
                return $this->data;
                break;
        }
    }

}
