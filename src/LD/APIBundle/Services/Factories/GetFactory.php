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
    public function process($data, $type, $graph = 'all', $format='short')
    {
                
        $func = 'get' . ucfirst($type);

        $this->data = call_user_func_array(
            array($this, $func),
            array($data, $graph, $type, $format)
        );
        /* Debug code for outputing the EasyRDF object
        return $this->data = array(
            'object_id' => $type . ' ' . $graph,
            'object_name' => 'Unkown object',
            'metadata_url' => $this->getContainer()->get('router')->generate('ld_api_api_index',array(),true),
            'data' => print_r($data, true),
        );*/
    }

    
    
    
    /**
     * Parse the results and build the response data array
     *
     * @param mixed  $rdf An EasyRDF object containing the results of a construct query
     * @param string $graph The name of the graph it use.
     *
     * @return array
     */
    
    function getDocuments($rdf, $graph, $type, $format) {
        echo $format;
        
        $data = $rdf['default'];
        
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
            
            //Note - we currently don't implement category_subject as this data is not captured in the R4D RDF or in the data import coming from ELDIS. 
            
            $document = array();
            //We add this custom property not originally in the IDS API
            $document['linked_data_uri'] = $resource->getUri(); 
            $document['metadata_url'] = $this->getContainer()->get('router')->generate('ld_api_api_index',array(),true).$resource_graph."/get/documents/".$resource_id."/full";
            $document['object_id'] = $resource->get("dcterms:identifier")->getValue();
            $document['object_type'] = "Document";
            $document['title'] = $resource->get("dcterms:title")->getValue();
            
            if(strtolower($format)=='full') {
                foreach($resource->all("dcterms:creator") as $author) {
                    $document['author'][] = $author->getValue();
                }

                foreach($resource->all("dcterms:coverage") as $coverage) {
                    if($coverage->hasProperty("http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeISO2")) {
                        $document['country_focus_array']->Country[] = array("alternative_name"=>$coverage->get("rdfs:label")->getValue(),
                                                                              "iso_two_letter_code"=>$coverage->get("<http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeISO2>")->getValue(),
                                                                              "metadata_url"=> $this->getContainer()->get('router')->generate('ld_api_api_index',array(),true).$graph."/get/countries/".$coverage->get("<http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeISO2>")->getValue()."/full",
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
                                                                              "metadata_url"=> $this->getContainer()->get('router')->generate('ld_api_api_index',array(),true).$graph."/get/regions/".$coverageID."/full",
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
                                                                        "metadata_url" => $this->getContainer()->get('router')->generate('ld_api_api_index',array(),true).$graph."/get/themes/".$themeID,
                                                                        "object_id" => $themeID,
                                                                        "object_name" => $theme->get("rdfs:label")->getValue(),
                                                                        "object_type"=>"theme");
                    $document['category_theme_ids'][] = $themeID;

                    //When easy RDF is correctly fetching then we can use skos:narrower properties to output category theme paths here. 
                }



                $document['license_type'] = "Not Known"; //ToDo - get license data into system
                $document['name'] = $resource->get("dcterms:title")->getValue();
                $document['publication_date'] = str_replace("T"," ",$resource->get("dcterms:date")->getValue());
                $document['publication_year'] = date("Y",strtotime($resource->get("dcterms:date")->getValue()));

                $document['publisher'] = $resource->get("dcterms:publisher/foaf:name") ? $resource->get("dcterms:publisher/foaf:name")->getValue() : null;

                //ToDo - Add more publisher details here (waiting for cache to clear)

                $document['site'] = explode("/",parse_url($resource->getUri())['path'])[1];



                $document['urls'][] = $resource->get("<http://purl.org/ontology/bibo/uri>") ? $resource->get("<http://purl.org/ontology/bibo/uri>")->getURI() : null;

                $document['website_url'] = $resource->get("rdfs:seeAlso") ? $resource->get("rdfs:seeAlso")->getUri() : null;
            }
            
            $results[] = $document;
        }
        
        $metadata = $this->buildMetaData("Unknown");
        
        
        return array("results"=>$results, "metadata"=>$metadata);
    
    }
    
     /**
     * Parse the results and build the response data array
     *
     * @param mixed  $rdf  An EasyRDF object containing the results of a construct query
     * @param string $graph The name of the graph it use.
     *
     * @return array
     */
     function getCountries($rdf, $graph, $type, $format) {
        
        $data = $rdf['default'];
        
        //If we have count then this is a multiple get
        if(array_key_exists('count',$rdf)) {
            $count = $rdf['count'];       
        
            //First we get our counts
            foreach($count as $row) {
                $total_results = $row->count->getValue();
            }
            $metadata = $this->buildMetaData($total_results);
            $single_country = false;
        } else {
            $single_country = true;
        }
        
        //Now we build the array
        if($data->isEmpty()) {
            return array();
        }
        
        $results = array();       
              
        foreach($data->allOfType("skos:Concept") as $country) {
            $document = array();
            
            $resource_url = explode('/',parse_url($country->getUri(),PHP_URL_PATH));
            
            if($resource_url[1] == "countryprofiles") { //In this case we're dealing with the FAO identifiers; our shared set
                $resource_graph = 'all';
            } else {
                $resource_graph = $resource_url[1];
            }
            
 
            
            $document['title'] = $country->get("rdfs:label")->getValue();
            $document['object_id'] = $country->hasProperty("dcterms:identifier") ? $country->get("dcterms:identifier")->getValue() : null;
            $document['iso_two_letter_code'] = $country->get("<http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeISO2>")->getValue();
            $document['object_type'] = 'Country';
            
            $resource_id = $document['object_id'] ? $document['object_id'] : $document['iso_two_letter_code']; 
            $document['metadata_url'] = $this->getContainer()->get('router')->generate('ld_api_api_index',array(),true).$graph."/get/countries/".$resource_id."/full/".$country->get("rdfs:label")->getValue();
            $document['linked_data_uri'] = $country->getUri(); 
            
            if($format == 'full') {
                $document['alternative_name'] = $country->get("rdfs:label")->getValue();
                $document['title'] = $country->get("rdfs:label")->getValue();
                $document['object_type'] = 'Country';
                $document['site'] = $resource_graph;
                $document['object_id'] = $country->hasProperty("dcterms:identifier") ? $country->get("dcterms:identifier")->getValue() : null;
                $document['iso_number'] = $country->hasProperty("http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeUN") ? $country->get("<http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeUN>")->getValue() : null;
                $document['country_name'] = $country->get("rdfs:label")->getValue();
                $document['iso_three_letter_code'] = $country->hasProperty("http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeISO3") ? $country->get("<http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeISO3>")->getValue() : null;
                
                foreach($country->all("<http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/isInGroup>") as $region) {
                    $region_doc = array();
                    $region_doc['archived'] = 'false';
                    $region_doc['deleted'] = 0;
                    $region_doc['object_id'] = $region->hasProperty("dcterms:identifier") ? $region->get("dcterms:identifier")->getValue() : null;
                    $region_doc['uncode'] = $region->hasProperty("http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeUN") ? $region->get("<http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeUN>")->getValue() : null;
                    $region_doc['object_name'] = $region->get("rdfs:label")->getValue();
                    $region_doc['object_type'] = 'region';
                            
                    $regionID = $region_doc['object_id'] ? $region_doc['object_id'] : $region_doc['uncode'];
                    $region_doc['metadata_url'] = $this->getContainer()->get('router')->generate('ld_api_api_index',array(),true).$graph."/get/regions/".$regionID."/full/".$this->stringToURL($region->get("rdfs:label")->getValue());
                            
                    $document['category_region_array'][] = $region_doc;
                    $document['category_region_ids'][] = $regionID;
                }
            }
            
           
            $results[] = $document;
        }
        
        if($single_country) {
            //Sort to get the version without an object_id first, as this is more likely to be the general purpose ID
            array_multisort($results,SORT_ASC,SORT_REGULAR);
            return array("results"=>$results);
        } else {
            return array("results"=>$results, "metadata"=>$metadata);
        }
        
     }
     
     /**
     * Parse the results and build the response data array
     *
     * @param mixed  $rdf  An EasyRDF object containing the results of a construct query
     * @param string $graph The name of the graph it use.
     *
     * @return array
     */
     function getRegions($rdf, $graph, $type, $format) {
        
        $data = $rdf['default'];
        if(array_key_exists('count',$rdf)) {
            $count = $rdf['count'];       
        
            //First we get our counts
            foreach($count as $row) {
                $total_results = $row->count->getValue();
            }
            $metadata = $this->buildMetaData($total_results);
            $single_region = false;
        } else {
            $single_region = true;
        }     
        
        
        
        //Now we build the array
        if($data->isEmpty()) {
            return array();
        }
        
        $return = array();       
        #Currently we don't seem to be getting the SKOS concept type coming through. Need to find another way to iterate...
              
        foreach($data->allOfType("<http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/geographical_region>") as $region) {
            if($format == 'full') {
                $document['archived'] = "false";
            }
            $document['title'] = $region->get("rdfs:label")->getValue();
            $document['object_type'] = "region";
           
            if($region->hasProperty("dcterms:identifier")) {
                $document['object_id'] = $region->get("dcterms:identifier")->getValue();
                $document['category_id'] = $region->get("dcterms:identifier")->getValue();
            }
            if($region->hasProperty("http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeUN")) {
                //Prefer the UN code if available.
                $document['object_id'] = $region->get("<http://www.fao.org/countryprofiles/geoinfo/geopolitical/resource/codeUN>")->getValue();
            }
            $document['category_path'] = $region->get("rdfs:label")->getValue();
            
            $document['metadata_url'] = $this->getContainer()->get('router')->generate('ld_api_api_index',array(),true).$graph."/get/regions/".$document['object_id']."/full/".$this->stringToURL($region->get("rdfs:label")->getValue());
            $document['linked_data_uri'] = $region->getUri(); 
            
            
            $results[] = $document;
        }
        
        if($single_region) {
            return array("results"=>$results);
        } else {
            return array("results"=>$results, "metadata"=>$metadata);
        }
        
        
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
    
    
   /**
    * Format a string to act as a URL by replacing spaces
    */
    public function stringToURL($string) {
        return str_replace(" ","_",$string);        
    }

}
