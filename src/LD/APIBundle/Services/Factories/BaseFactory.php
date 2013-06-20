<?php
namespace LD\APIBundle\Services\Factories;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base entity factory
 */
abstract class BaseFactory implements FactoryInterface
{
    protected $container = null;

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Get the container object
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get as human readable type for an object
     *
     * @param mixed $obj An array, object or literal
     *
     * @return string
     */
    public function getType($obj)
    {
        if (is_object($obj)) {

            return get_class($obj);
        }

        return gettype($obj);
    }
    
        
    /**
     * Build the metadata block to be presented with results
     *
     * @param 
     *
     * @return array
     */
    public function buildMetaData($result_count = 0,$req = null) {
        
        $_req = ($req) ? $req : Request::createFromGlobals();
        $offset = $_req->query->get('start_offset',0);
        
        return array("num_results"=>$result_count, "start_offset"=>$offset);
        
        //ToDo: Need to build the 'Next Page' URL here.
        //E.g. "next_page": "http://api.ids.ac.uk/openapi/bridge/get_all/countries/full?num_results=10&start_offset=10",
        // $this->getContainer()->get('router')->generate('ld_api_api_index');
        // I wasn't sure the best way to get the query parameters (e.g. get / get_all), countryies/resgions etc. 
        
    }

}
