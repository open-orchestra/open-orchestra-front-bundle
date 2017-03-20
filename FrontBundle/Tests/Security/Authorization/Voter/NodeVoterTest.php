<?php

namespace OpenOrchestra\FrontBundle\Tests\Security\Authorization\Voter;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\FrontBundle\Security\Authorization\Voter\NodeVoter;
use OpenOrchestra\FrontBundle\Security\ContributionActionInterface;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use Phake;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;


/**
 * Class NodeVoterTest
 */
class NodeVoterTest  extends AbstractBaseTestCase
{
    protected $token;
    protected $accessDecisionManager;
    protected $voter;

    /**
     * Set up the test
     */
    public function setUp()
    {
        parent::setUp();
        $this->accessDecisionManager = Phake::mock('Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface');
        $this->token = Phake::mock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->voter = new NodeVoter($this->accessDecisionManager);
    }

    /**
     * @param mixed  $subject
     * @param string $attribute
     * @param array  $tokenRoles
     * @param array  $subjectRoles
     * @param int    $expectedVote
     *
     * @dataProvider provideDataToVote
     */
    public function testVote($subject, $attribute, array $tokenRoles, array $subjectRoles, $expectedVote)
    {
        Phake::when($this->token)->getRoles()->thenReturn($tokenRoles);
        if ($subject instanceof ReadNodeInterface) {
            Phake::when($subject)->getFrontRoles()->thenReturn($subjectRoles);
        }

        $vote = $this->voter->vote($this->token, $subject, array($attribute));

        $this->assertEquals($expectedVote, $vote);
    }

   /**
     * @return array
     */
    public function provideDataToVote()
    {
        return array(
            array(
                Phake::mock('OpenOrchestra\ModelInterface\Model\ReadNodeInterface'),
                ContributionActionInterface::READ,
                array('ROLE_SITE_0'),
                array('ROLE_SITE_0'),
                Voter::ACCESS_GRANTED
            ),
            array(
                Phake::mock('OpenOrchestra\ModelInterface\Model\ReadNodeInterface'),
                ContributionActionInterface::READ,
                array('ROLE_SITE_0'),
                array('ROLE_SITE_1'),
                Voter::ACCESS_DENIED
            ),
            array(
                new \stdClass(),
                ContributionActionInterface::READ,
                array('ROLE_SITE_0'),
                array('ROLE_SITE_0'),
                Voter::ACCESS_ABSTAIN
            ),
            array(
                Phake::mock('OpenOrchestra\ModelInterface\Model\ReadNodeInterface'),
                'test',
                array('ROLE_SITE_0'),
                array('ROLE_SITE_0'),
                Voter::ACCESS_ABSTAIN
            ),
        );
    }
}
