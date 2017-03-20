<?php

namespace OpenOrchestra\FrontBundle\Security\Authorization\Voter;

use OpenOrchestra\FrontBundle\Security\ContributionActionInterface;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Class NodeVoter
 *
 * Voter checking rights on node management
 */
class NodeVoter extends Voter
{
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
        return empty($subject->getFrontRoles()) || !empty(array_intersect($token->getRoles(), $subject->getFrontRoles()));
    }
}
