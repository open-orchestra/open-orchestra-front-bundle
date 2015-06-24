<?php

namespace OpenOrchestra\FrontBundle\Routing\Generator\Dumper;

use Symfony\Component\Routing\Generator\Dumper\PhpGeneratorDumper;

/**
 * Class PhpGeneratorDumper
 */
class OpenOrchestraGeneratorDumper extends PhpGeneratorDumper
{
    /**
     * Dumps a set of routes to a PHP class.
     *
     * Available options:
     *
     *  * class:      The class name
     *  * base_class: The base class name
     *
     * @param array $options An array of options
     *
     * @return string A PHP class representing the generator class
     *
     * @api
     */
    public function dump(array $options = array())
    {
        $options = array_merge(array(
            'class' => 'ProjectUrlGenerator',
            'base_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator',
        ), $options);

        return <<<EOF
<?php

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;
use OpenOrchestra\FrontBundle\Manager\NodeManager;
use OpenOrchestra\DisplayBundle\Exception\NodeNotFoundException;

/**
 * {$options['class']}
 *
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class {$options['class']} extends {$options['base_class']}
{
    private static \$declaredRoutes = {$this->generateDeclaredRoutes()};
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

{$this->generateGenerateMethod()}

{$this->generateSetAliasIdMethod()}

{$this->generateGetAliasIdMethod()}
}

EOF;
    }

    /**
     * Generates PHP code representing an array of defined routes
     * together with the routes properties (e.g. requirements).
     *
     * @return string PHP code
     */
    private function generateDeclaredRoutes()
    {
        $routes = "array(\n";
        foreach ($this->getRoutes()->all() as $name => $route) {
            $compiledRoute = $route->compile();

            $properties = array();
            $properties[] = $compiledRoute->getVariables();
            $properties[] = $route->getDefaults();
            $properties[] = $route->getRequirements();
            $properties[] = $compiledRoute->getTokens();
            $properties[] = $compiledRoute->getHostTokens();
            $properties[] = $route->getSchemes();

            $routes .= sprintf("        '%s' => %s,\n", $name, str_replace("\n", '', var_export($properties, true)));
        }
        $routes .= '    )';

        return $routes;
    }

    /**
     * Generates PHP code representing the `generate` method that implements the UrlGeneratorInterface.
     *
     * @return string PHP code
     */
    private function generateGenerateMethod()
    {
        return <<<EOF
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
EOF;
    }

    /**
     * generate PHP code representing the setAliasId method
     *
     * @return string
     */
    private function generateSetAliasIdMethod()
    {
        return <<<EOF
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
EOF;
    }

    /**
     * generate PHP code representing the getAliasId method
     *
     * @return string
     */
    private function generateGetAliasIdMethod()
    {
        return <<<EOF
    private function getAliasId()
    {
        return \$this->aliasId;
    }
EOF;
    }
}
