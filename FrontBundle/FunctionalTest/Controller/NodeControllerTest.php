<?php

namespace PHPOrchestra\FrontBundle\FunctionalTest\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class NodeControllerTest
 */
class NodeControllerTest extends WebTestCase
{
    /**
     * Test fixture_home
     */
    public function testShowActionFixtureHome()
    {
        $client = static::createClient();
        $client->setServerParameter('SYMFONY__SITE', '1');

        $crawler = $client->request('GET', '');

        $this->assertGreaterThan(0, $crawler->filter('html:contains("Bienvenu sur le site de démo issu des fixtures.")')->count());
        $this->assertEquals(0, $crawler->filter('html:contains("Business & Decision est un Groupe international de services numériques")')->count());
    }

    /**
     * Test fixture_home
     */
    public function testShowActionFixtureHomeSecondSite()
    {
        $client = static::createClient();
        $client->setServerParameter('SYMFONY__SITE', '2');

        $crawler = $client->request('GET', '');

        $this->assertEquals(0, $crawler->filter('html:contains("Bienvenu sur le site de démo issu des fixtures.")')->count());
        $this->assertGreaterThan(0, $crawler->filter('html:contains("Business & Decision est un Groupe international de services numériques")')->count());
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
        $client = static::createClient();
        $client->setServerParameter('SYMFONY__SITE', '1');

        $crawler = $client->request('GET', '');
        $form = $crawler->selectButton('Login')->form();
        $form['_username'] = 'nicolas';
        $form['_password'] = 'nicolas';
        $crawler = $client->submit($form);

        $crawler = $client->request('GET', '/node/fixture_full');

        $this->assertGreaterThan(0, $crawler->filter('html:contains("Qui sommes-nous?")')->count());

        $this->assertGreaterThanOrEqual(11, $crawler->filter('a')->count());

        $link = $crawler->filter('a:contains("Fixture B&D")')->eq(1)->link();

        $crawler = $client->click($link);

        $this->assertGreaterThan(0, $crawler->filter('html:contains("Tout sur B&D ")')->count());
    }

    /**
     * Test fixture_full
     * if there are the good text
     * send search with fixture
     * test the number of link
     * if there are the good text
     * clique on a link with text Nous contacter
     * test if the status page is 404
     */
    public function testShowActionFixtureFull2()
    {
        $client = static::createClient();
        $client->setServerParameter('SYMFONY__SITE', '1');

        $crawler = $client->request('GET', '');
        $form = $crawler->selectButton('Login')->form();
        $form['_username'] = 'nicolas';
        $form['_password'] = 'nicolas';
        $crawler = $client->submit($form);

        $crawler = $client->request('GET', '/node/fixture_full');

        $this->assertGreaterThan(0, $crawler->filter('html:contains("Aliquam convallis facilisis nulla")')->count());

        $form = $crawler->selectButton('Rechercher')->form();

        $form['autocomplete_search[terms]'] = 'fixture';

        $crawler = $client->submit($form);

        $this->assertGreaterThanOrEqual(14, $crawler->filter('a')->count());

//        $this->assertGreaterThan(0, $crawler->filter('html:contains("Le bottin mondain")')->count());

        $link = $crawler->filter('a:contains("Fixture Contact Us")')->eq(1)->link();

        $client->click($link);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
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
        $client = static::createClient();
        $client->setServerParameter('SYMFONY__SITE', '1');

        $crawler = $client->request('GET', '/node/fixture_search');

        $this->assertGreaterThan(0, $crawler->filter('html:contains("No result found")')->count());

        $form = $crawler->selectButton('Rechercher')->form();

        $form['autocomplete_search[terms]'] = 'lorem';

        $crawler = $client->submit($form);

        $this->assertGreaterThanOrEqual(14, $crawler->filter('a')->count());

//        $this->assertGreaterThan(0, $crawler->filter('html:contains("Lorem ipsum dolor sit amet")')->count());

        $link = $crawler->filter('a:contains("Directory")')->link();

        $client->click($link);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
