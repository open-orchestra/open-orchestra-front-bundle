<?php

namespace OpenOrchestra\FrontBundle\Security\Authorization\Voter;

use OpenOrchestra\FrontBundle\Security\ContributionActionInterface;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class NodeVoter
 *
 * Voter checking rights on node management
 */
class NodeVoter extends Voter implements ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param string $attribute
     * @param mixed  $subject
     *
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        return $attribute == ContributionActionInterface::READ && $subject instanceof ReadNodeInterface;
    }

    /**
     * @param string         $attribute
     * @param mixed          $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        return $this->supports($attribute, $subject) ? $this->containsRole($token, $subject) : false;
    }

    /**
     * @param TokenInterface    $token
     * @param ReadNodeInterface $subject
     *
     * @return bool
     */
    protected function containsRole(TokenInterface $token, ReadNodeInterface $subject)
    {
        return empty($subject->getFrontRoles())
            || ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')
                && !empty(array_intersect($token->getRoles(), $subject->getFrontRoles())));
    }
}
