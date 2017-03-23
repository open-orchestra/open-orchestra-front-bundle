<?php

namespace OpenOrchestra\FrontBundle\Security\Authorization\Voter;

use OpenOrchestra\FrontBundle\Security\ContributionActionInterface;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * Class NodeVoter
 *
 * Voter checking rights on node
 */
class NodeVoter extends Voter
{

    private $decisionManger;
    /**
     *
     */
    public function __construct(
        AccessDecisionManagerInterface $accessDecisionManger,
        RoleHierarchyInterface $roleHierarchy
    ) {
        $this->accessDecisionManger = $accessDecisionManger;
        $this->roleHierarchy = $roleHierarchy;
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
        $tokenRoles = $this->roleHierarchy->getReachableRoles($token->getRoles());;
        foreach ($tokenRoles as $key => $role) {
            if ($role instanceof RoleInterface) {
                $tokenRoles[$key] = $role->getRole();
            }
        }

        return empty($subject->getFrontRoles())
            || ($this->accessDecisionManger->decide($token, array('IS_AUTHENTICATED_FULLY'))
                && !empty(array_intersect($tokenRoles, $subject->getFrontRoles())));
    }
}
