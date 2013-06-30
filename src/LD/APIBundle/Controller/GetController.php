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
class GetController extends APIController
{
    /**
     * @param string $graph  the graph to use, see service.yml
     * @param string $object documents|assets|countries|themes|organisations|regions
     * @param string $id     the object id
     * @param string $format short|full
     * @param string $name  A name of the object (used by country etc.)
     *
     * @Route(
     *      "/{graph}/get/{object}/{id}",
     *      requirements={
     *          "object" = "documents|assets|countries|themes|organisations|regions",
     *      },
     *      defaults={
     *          "format" = "short",
     *      }
     * )
     * @Route(
     *      "/{graph}/get/{object}/{id}/",
     *      requirements={
     *          "object" = "documents|assets|countries|themes|organisations|regions",
     *      },
     *      defaults={
     *          "format" = "short",
     *      }
     * )
     * @Route(
     *      "/{graph}/get/{object}/{id}/{format}",
     *      requirements={
     *          "object" = "documents|assets|countries|themes|organisations|regions",
     *      }
     * )
     * @Route(
     *      "/{graph}/get/{object}/{id}/{format}/",
     *      requirements={
     *          "object" = "documents|assets|countries|themes|organisations|regions",
     *      }
     * )
     * @Route(
     *      "/{graph}/get/{object}/{id}/{format}/{name}",
     *      requirements={
     *          "object" = "documents|assets|countries|themes|organisations|regions",
     *      }
     * )
     * @Method({"GET", "HEAD", "OPTIONS"})
     * @return Response
     */
    public function getAction($graph, $object, $id, $format, $name = '')
    {
        // get and set  the query factory
        $querybuilders = $this->container->getParameter('querybuilder');
        if (isset($querybuilders['get'][$object])) {
            $builder = $querybuilders['get'][$object];
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
        $spql = $spqls['get'][$object];

        // fetch factory
        $entfactories = $this->container->getParameter('factories');
        $this->container->get('logger')->info(
            sprintf('Fetching factory: get->%s', $object)
        );
        $factoryClass = $entfactories['get'][$object];

        $response = $this->chomp($graph, $spql, $factoryClass, $builder, $format, $object);

        return $this->response($response);
    }

    /**
     * @param string $graph  the graph to use, see service.yml
     * @param string $object documents|assets|countries|themes|organisations|region
     * @param string $format     required format
     *
     * @Route(
     *      "/{graph}/get_all/{object}",
     *      requirements={
     *          "object" = "documents|assets|countries|themes|organisations|regions",
     *      }
     * )
     * @Route(
     *      "/{graph}/get_all/{object}/",
     *      requirements={
     *          "object" = "documents|assets|countries|themes|organisations|regions",
     *      }
     * )
     * @Route(
     *      "/{graph}/get_all/{object}/{format}",
     *      requirements={
     *          "object" = "documents|assets|countries|themes|organisations|regions",
     *      }
     * )
     * @Method({"GET", "HEAD", "OPTIONS"})
     * @return Response
     */
    public function getAllAction($graph, $object, $id = null, $format = 'short')
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