<?php

namespace OpenOrchestra\FrontBundle\FunctionalTest\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

/**
 * Class NodeControllerTest
 */
class NodeControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * Set up test
     */
    public function setUp()
    {
        $this->client = static::createClient();
    }

    /**
     * Test fixture_home
     */
    public function testShowActionFixtureHomeSecondSite()
    {
        $this->client->setServerParameter('HTTP_HOST', 'demo.openorchestra.dev');
        $crawler = $this->client->request('GET', '');

        $this->assertCount(0, $crawler->filter('html:contains("Bienvenu sur le site de démo issu des fixtures.")'));
        $this->assertGreaterThan(0, $crawler->filter('html:contains("Business & Decision est un Groupe international de services numériques")')->count());
    }

    /**
     * Test fixture_home
     *
     * @param string $currentLanguage
     * @param string $otherLanguage
     *
     * @dataProvider provideLanguageAndOtherLanguage
     */
    public function testShowActionFixtureHomeSiteEchonext($currentLanguage, $otherLanguage)
    {
        $this->client->setServerParameter('HTTP_HOST', 'echonext.openorchestra.dev');
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', '/' . $currentLanguage);

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(0, $crawler->filter('html:contains("Bienvenu sur le site de démo issu des fixtures.")'));
        $this->assertCount(0, $crawler->filter('html:contains("Business & Decision est un Groupe international de services numériques")'));
        $this->assertCount(1755, $crawler->filter('#contentNewsList > div'));
        foreach ($crawler->filter('a')->extract('href') as $link) {
            if (strpos($link, 'echonext.phporchestra.dev') && strpos($link, 'news')) {
                $this->assertRegExp('/'. $currentLanguage . '/', $link);
                $this->assertNotRegExp('/\/' . $otherLanguage . '\//', $link);
            }
        }
    }

    /**
     * @return array
     */
    public function provideLanguageAndOtherLanguage()
    {
        return array(
            array('fr', 'en'),
            array('en', 'fr'),
        );
    }

    /**
     * Test fixture_full
     * if there is the good text
     * the number of link
     * click on one link
     * test if we are redirect on the good page
     */
    public function testShowActionFixtureFull()
    {
        $this->markTestSkipped();

        $crawler = $this->client->request('GET', '');
        $form = $crawler->selectButton('Connexion')->form();
        $form['_username'] = 'nicolas';
        $form['_password'] = 'nicolas';
        $crawler = $this->client->submit($form);

        $crawler = $this->client->request('GET', '/fixture-full');

        $this->assertGreaterThan(0, $crawler->filter('html:contains("Qui sommes-nous?")')->count());
        $this->assertGreaterThanOrEqual(11, $crawler->filter('a')->count());

        $link = $crawler->filter('a:contains("Fixture B&D")')->eq(1)->link();
        $crawler = $this->client->click($link);
        //$this->assertGreaterThan(0, $crawler->filter('html:contains("Tout sur B&D ")')->count());

        $crawler = $this->client->request('GET', '/fixture-full');

        $this->assertGreaterThan(0, $crawler->filter('html:contains("Aliquam convallis facilisis nulla")')->count());

        $form = $crawler->selectButton('Rechercher')->form();
        $form['autocomplete_search[terms]'] = 'fixture';
        $crawler = $this->client->submit($form);

        $this->assertGreaterThanOrEqual(14, $crawler->filter('a')->count());

//        $this->assertGreaterThan(0, $crawler->filter('html:contains("Le bottin mondain")')->count());
//        $link = $crawler->filter('a:contains("Fixture Contact Us")')->eq(1)->link();
//        $client->click($link);
//        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * Test fixture search
     * if text no result found is on the page
     * end search with lorem
     * count number of link on the page
     * if there are the good text
     * count number of img on the page
     */
    public function testShowActionSearch()
    {
        $this->markTestSkipped();

        $crawler = $this->client->request('GET', '/node/fixture_search');

        $this->assertGreaterThan(0, $crawler->filter('html:contains("Aucun résultat trouvé")')->count());

        $form = $crawler->selectButton('Rechercher')->form();

        $form['autocomplete_search[terms]'] = 'lorem';

        $crawler = $this->client->submit($form);

        $this->assertGreaterThanOrEqual(14, $crawler->filter('a')->count());

//        $this->assertGreaterThan(0, $crawler->filter('html:contains("Lorem ipsum dolor sit amet")')->count());

//        $link = $crawler->filter('a:contains("Directory")')->link();

//        $this->client->click($link);

//        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
