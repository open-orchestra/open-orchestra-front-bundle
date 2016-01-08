<?php

namespace OpenOrchestra\FrontBundle\Tests\Routing\Database\Transformer;

use Doctrine\Common\Collections\ArrayCollection;
use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\FrontBundle\Routing\Database\Transformer\RouteDocumentCollectionToRouteCollectionTransformer;
use Phake;

/**
 * Test RouteDocumentCollectionToRouteCollectionTransformerTest
 */
class RouteDocumentCollectionToRouteCollectionTransformerTest extends AbstractBaseTestCase
{
    /**
     * @var RouteDocumentCollectionToRouteCollectionTransformer
     */
    protected $transformer;

    protected $routeTransformer;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->routeTransformer = Phake::mock('OpenOrchestra\FrontBundle\Routing\Database\Transformer\RouteDocumentToValueObjectTransformer');

        $this->transformer = new RouteDocumentCollectionToRouteCollectionTransformer($this->routeTransformer);
    }

    /**
     * Test transform
     */
    public function testTransform()
    {
        $routeDocument = Phake::mock('OpenOrchestra\ModelInterface\Model\RouteDocumentInterface');
        Phake::when($routeDocument)->getName()->thenReturn('first')->thenReturn('second');
        $routeDocuments = new ArrayCollection(array($routeDocument, $routeDocument));

        $route = Phake::mock('Symfony\Component\Routing\Route');
        Phake::when($this->routeTransformer)->transform(Phake::anyParameters())->thenReturn($route);

        $routeCollection = $this->transformer->transform($routeDocuments);

        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $routeCollection);
        $this->assertCount(2, $routeCollection);
        Phake::verify($this->routeTransformer, Phake::times(2))->transform($routeDocument);
    }
}
