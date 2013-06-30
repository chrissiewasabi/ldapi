<?php

namespace LD\APIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use LD\APIBundle\Entity\Region;
use LD\APIBundle\Entity\Theme;

/**
 * Get controller
 *
 * @see http://api.ids.ac.uk/
 */
class SearchController extends APIController
{
    /**
     * @param string $graph  the graph to use, see service.yml
     * @param string $object documents|assets|countries|themes|organisations|regions
     * @param string $format short|full
     *
     * @Route(
     *      "/{graph}/search/{object}",
     *      requirements={
     *          "object" = "documents|assets|countries|themes|organisations|regions",
     *      }
     * )
     * @Route(
     *      "/{graph}/search/{object}/",
     *      requirements={
     *          "object" = "documents|assets|countries|themes|organisations|regions",
     *      }
     * )
     * @Route(
     *      "/{graph}/search/{object}/{format}",
     *      requirements={
     *          "object" = "documents|assets|countries|themes|organisations|regions",
     *      }
     * )
     * @Route(
     *      "/{graph}/search/{object}/{format}/",
     *      requirements={
     *          "object" = "documents|assets|countries|themes|organisations|regions",
     *      }
     * )
     * @Method({"GET", "HEAD", "OPTIONS"})
     * @return Response
     */
    public function searchAction($graph, $object, $format = 'short')
    {
        
        // get and set  the query factory
        $querybuilders = $this->container->getParameter('querybuilder');
        if (isset($querybuilders['get_all'][$object])) {
            $builder = $querybuilders['get_all'][$object];
        } elseif (isset($querybuilders['default'])) {
            $builder = $querybuilders['default'];
        } else {
            $builder = 'LD\APIBundle\Services\ids\DefaultQueryBuilder';
        }

        // get the sparql
        $spqls = $this->container->getParameter('sparqls');
        $this->container->get('logger')->info(
            sprintf('Fetching sparql: get->%s', $object)
        );
        $spql = $spqls['get_all'][$object];

        // fetch factory
        $entfactories = $this->container->getParameter('factories');
        $this->container->get('logger')->info(
            sprintf('Fetching factory: get->%s', $object)
        );
        $factoryClass = $entfactories['get'][$object];

        $response = $this->chomp($graph, $spql, $factoryClass, $builder, $format, $object);

        return $this->response($response);
    }
   
}
