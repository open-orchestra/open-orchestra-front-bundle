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
        $this->assertSame(<<<EOF
<?php

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;

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
    public function __construct(RequestContext \$context, RequestStack \$requestStack, LoggerInterface \$logger = null)
    {
        \$this->context = \$context;
        \$this->request = \$requestStack->getMasterRequest();
        \$this->logger = \$logger;
    }

    public function generate(\$name, \$parameters = array(), \$referenceType = self::ABSOLUTE_PATH)
    {
        if (!isset(self::\$declaredRoutes[\$name])) {
            if (!isset(self::\$declaredRoutes[\$this->getAliasId() . '_' . \$name])) {
                throw new RouteNotFoundException(sprintf('Unable to generate a URL for the named route "%s" as such route does not exist.', \$name));
            }

            \$name = \$this->getAliasId() . '_' . \$name;
        }

        list(\$variables, \$defaults, \$requirements, \$tokens, \$hostTokens, \$requiredSchemes) = self::\$declaredRoutes[\$name];

        return \$this->doGenerate(\$variables, \$defaults, \$requirements, \$tokens, \$parameters, \$name, \$referenceType, \$hostTokens, \$requiredSchemes);
    }

    private function getAliasId()
    {
        if (\$this->aliasId === null) {
            \$this->aliasId = \$this->request->get('aliasId', 0);
        }

        return \$this->aliasId;
    }
}

EOF
        , $this->dumper->dump());
    }
}
