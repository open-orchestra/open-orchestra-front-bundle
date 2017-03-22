<?php

namespace OpenOrchestra\FrontBundle\Security\Authorization\Voter;

use OpenOrchestra\FrontBundle\Security\ContributionActionInterface;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * Class NodeVoter
 *
 * Voter checking rights on node management
 */
class NodeVoter extends Voter implements ContainerAwareInterface
{

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
        $tokenRoles = $this->get('security.role_hierarchy')->getReachableRoles($token->getRoles());;
        foreach ($tokenRoles as $key => $role) {
            if ($role instanceof RoleInterface) {
                $tokenRoles[$key] = $role->getRole();
            }
        }

        return empty($subject->getFrontRoles())
            || ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')
                && !empty(array_intersect($tokenRoles, $subject->getFrontRoles())));
    }
}
