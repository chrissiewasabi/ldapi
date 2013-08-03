<?php
namespace LD\APIBundle\Services\QueryBuilders;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Wrapper to making easy rdf sparql queries
 */
class GetQueryBuilder extends DefaultQueryBuilder
{
    /**
     * Build a sparql query.
     *
     * This funtion expects to get an array of query elements as the first
     * paramter.  See the LD\APIBundle\Resources\config\services.yml for an
     * example.
     *
     * It requires that the array has at least two elements, select and where,
     * these are then glued together to make a sparql query.  So a simple query
     * would be:
     *
     *     array(
     *       'select' => 'select count(*)',
     *       'where' => 'where {?a ?b ?c}',
     *     );
     *
     * In addtion there can be a define index that will allow name spaces to be
     * added.
     *
     * @param array  $elements The query in the form of an array
     * @param string $graph    The graph to access
     * @param array  $data     Parameters that are available to be replaced in the query
     *
     * @return string
     */
    public function createQuery(array $elements, $graph = null, $data = array())
    {
        
        // $request = Request::createFromGlobals();
        $request = $this->container->get('request');
        $params = $request->attributes->get('_route_params');
        $this->container->get('logger')->info(json_encode($params));
        
        
        $_id = $params['id'];
	$_graph = $params['graph'];
        
        
        
        /** For now we base graph selection on the ID. 
         * 
         * ELDIS IDs start with A, whereas R4D are numerical.
         * 
         * Graph will already be respected by the graph query.
         * 
         */
        if(substr($_id,0,1) == 'A') {
            $uri = (string)$this->container->parameters["graphs"]["eldis"] . "output/" . $_id . "/";
            
        } else {
            $uri = (string)$this->container->parameters["graphs"]["r4d"] . "output/" . $_id  . "/";
        }
        
        $query = str_replace(
            '__URI__',
            $uri,
            parent::createQuery($elements, $graph)
        );
        
        $query = str_replace(
            '__ID__',
            $_id,
            parent::createQuery($elements, $graph)
        );

        return $query;
    }

}
