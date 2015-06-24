<?php

namespace OpenOrchestra\FrontBundle\Tests\Routing\Dumper;

use OpenOrchestra\FrontBundle\Routing\Generator\Dumper\OpenOrchestraGeneratorDumper;
use Phake;

/**
 * Test OpenOrchestraGeneratorDumperTest
 */
class OpenOrchestraGeneratorDumperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OpenOrchestraGeneratorDumper
     */
    protected $dumper;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $routes = Phake::mock('Symfony\Component\Routing\RouteCollection');
        Phake::when($routes)->all()->thenReturn(array());

        $this->dumper = new OpenOrchestraGeneratorDumper($routes);
    }

    /**
     * Test dump
     */
    public function testDump()
    {
        $generatedString = <<<EOF
<?php

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;
use OpenOrchestra\FrontBundle\Manager\NodeManager;
use OpenOrchestra\DisplayBundle\Exception\NodeNotFoundException;

/**
 * ProjectUrlGenerator
 *
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class ProjectUrlGenerator extends Symfony\Component\Routing\Generator\UrlGenerator
{
    private static \$declaredRoutes = array(
    );
    private \$aliasId;

    /**
     * Constructor.
     */
    public function __construct(RequestContext \$context, RequestStack \$requestStack, NodeManager \$nodeManager, LoggerInterface \$logger = null)
    {
        \$this->context = \$context;
        \$this->request = \$requestStack->getMasterRequest();
        \$this->logger = \$logger;
        \$this->nodeManager = \$nodeManager;
    }

    public function generate(\$name, \$parameters = array(), \$referenceType = self::ABSOLUTE_PATH)
    {
        if (isset(\$parameters[self::REDIRECT_TO_LANGUAGE])) {
            try {
                \$name = \$this->nodeManager->getNodeRouteName(\$name, \$parameters[self::REDIRECT_TO_LANGUAGE]);
            } catch (NodeNotFoundException \$e) {
                throw new RouteNotFoundException(sprintf('Unable to generate a URL for the named route "%s" as such route does not exist.', \$name));
            }
            unset(\$parameters[self::REDIRECT_TO_LANGUAGE]);
        }

        if (!isset(self::\$declaredRoutes[\$name])) {
            \$aliasId = (isset(\$parameters['required']['aliasId'])) ? \$parameters['required']['aliasId'] : null;
            \$this->setAliasId(\$aliasId);

            \$name = \$this->getAliasId() . '_' . \$name;
            if (!isset(self::\$declaredRoutes[\$name])) {
                throw new RouteNotFoundException(sprintf('Unable to generate a URL for the named route "%s" as such route does not exist.', \$name));
            }
        }

        list(\$variables, \$defaults, \$requirements, \$tokens, \$hostTokens, \$requiredSchemes) = self::\$declaredRoutes[\$name];
        if (isset(\$parameters['required']['scheme'])) {
            \$requiredSchemes = array(\$parameters['required']['scheme']);
        }
        unset(\$parameters['required']);

        return \$this->doGenerate(\$variables, \$defaults, \$requirements, \$tokens, \$parameters, \$name, \$referenceType, \$hostTokens, \$requiredSchemes);
    }

    private function setAliasId(\$aliasId = null)
    {
        if (!is_null(\$aliasId)) {
            \$this->aliasId = \$aliasId;
        } else if (\$this->aliasId === null) {
            \$this->aliasId = 0;
            if (\$this->request) {
                \$this->aliasId = \$this->request->get('aliasId', 0);
            }
        }
    }

    private function getAliasId()
    {
        return \$this->aliasId;
    }
}

EOF;

        $this->assertSame($generatedString, $this->dumper->dump());
    }
}
