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
        return $this->data = array(
            'object_id' => $type . ' ' . $graph,
            'object_name' => 'Unkown object',
            'metadata_url' => $this->getContainer()->get('router')->generate('ld_api_api_index'),
            'data' => print_r($data, true),
        );
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
