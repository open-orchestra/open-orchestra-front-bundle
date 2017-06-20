<?php

namespace OpenOrchestra\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Config;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class HealthCheckController
 */
class HealthCheckController extends Controller
{
    /**
     * @Config\Route("/health-check", name="health_check")
     * @return Response
     */
    public function healthCheckAction()
    {
        $healthCheck = $this->get('open_orchestra_base.health_check');
        $result = $healthCheck->run();
        if ($result->isSuccess()) {
            return new Response('HealthCheck-Success');
        } else {
            return new Response('HealthCheck-Failure');
        }
    }

    /**
     * @Config\Route("/health-check-human", name="health_check_human")
     * @return Response
     */
    public function healthCheckHumanAction()
    {
        $healthCheck = $this->get('open_orchestra_base.health_check');
        $result = $healthCheck->run();

        return $this->render('OpenOrchestraFrontBundle:HealthCheck:health_check.html.twig', array(
            'result' => $result
        ));
    }
}
