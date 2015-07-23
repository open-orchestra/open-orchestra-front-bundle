<?php

namespace OpenOrchestra\FrontBundle\Tests\SubQuery\Strategies;

use OpenOrchestra\FrontBundle\SubQuery\Strategies\PostDataSubQueryStrategy;
use Phake;

/**
 * Class PostDataSubQueryStrategyTest
 */
class PostDataSubQueryStrategyTest extends AbstractSubQueryStrategyTest
{
    protected $request;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->request = Phake::mock('Symfony\Component\HttpFoundation\Request');
        $requestStack = Phake::mock('Symfony\Component\HttpFoundation\RequestStack');
        Phake::when($requestStack)->getMasterRequest()->thenReturn($this->request);

        $this->strategy = new PostDataSubQueryStrategy($requestStack);
    }

    /**
     * @return array
     */
    public function provideBlockParameterAndSupport()
    {
        return array(
            array('request.contentId', false),
            array('foo', false),
            array('request', false),
            array('post_data', true),
            array('post_data_empty', false),
        );
    }

    /**
     * Test name
     */
    public function testGetName()
    {
        $this->assertSame('post_data', $this->strategy->getName());
    }

    /**
     * Test generate
     */
    public function testGenerate()
    {
        $datas = array('foo' => 'bar');
        $attributes = Phake::mock('Symfony\Component\HttpFoundation\ParameterBag');
        Phake::when($attributes)->all()->thenReturn($datas);
        $this->request->request = $attributes;

        $this->assertSame($datas, $this->strategy->generate('post_data'));
    }
}
