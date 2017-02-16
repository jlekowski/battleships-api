<?php

namespace Tests\AppBundle\EventSubscriber;

use AppBundle\EventSubscriber\EntitySubscriber;
use Prophecy\Prophecy\ObjectProphecy;

class EntitySubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EntitySubscriber
     */
    protected $subscriber;

    /**
     * @var ObjectProphecy
     */
    protected $tokenStorage;

    /**
     * @var ObjectProphecy
     */
    protected $validator;

    /**
     * @var ObjectProphecy
     */
    protected $logger;

    public function setUp()
    {
        $this->tokenStorage = $this->prophesize('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage');
        $this->validator = $this->prophesize('Symfony\Component\Validator\Validator\ValidatorInterface');
        $this->logger = $this->prophesize('Psr\Log\LoggerInterface');

        $this->subscriber = new EntitySubscriber($this->tokenStorage->reveal(), $this->validator->reveal(), $this->logger->reveal());
    }

    public function testPreUpdate()
    {
        $eventArgs = $this->prophesize('Doctrine\ORM\Event\PreUpdateEventArgs');
        $eventArgs->getEntity()->willReturn('entity');

        $this->validator->validate('entity', null, ['update'])->shouldBeCalled();
        $this->subscriber->preUpdate($eventArgs->reveal());
    }

    public function testPrePersist()
    {
        $eventArgs = $this->prophesize('Doctrine\ORM\Event\LifecycleEventArgs');
        $eventArgs->getEntity()->willReturn('entity');

        $this->validator->validate('entity')->shouldBeCalled();
        $this->subscriber->prePersist($eventArgs->reveal());
    }

    public function testPostLoadSetsLoggerForLoggerAwareEntities()
    {
        $eventArgs = $this->prophesize('Doctrine\ORM\Event\LifecycleEventArgs');
        $entity = $this->prophesize('Psr\Log\LoggerAwareInterface');
        $eventArgs->getEntity()->willReturn($entity);

        $entity->setLogger($this->logger)->shouldBeCalled();

        $this->subscriber->postLoad($eventArgs->reveal());
    }

    public function testPostLoadSetsLoggedUser()
    {
        $eventArgs = $this->prophesize('Doctrine\ORM\Event\LifecycleEventArgs');
        $entity = $this->prophesize('AppBundle\Entity\Game');
        $token = $this->prophesize('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $user = $this->prophesize('AppBundle\Entity\User');

        $eventArgs->getEntity()->willReturn($entity);
        $this->tokenStorage->getToken()->willReturn($token);
        $token->getUser()->willReturn($user);

        $entity->setLoggedUser($user)->shouldBeCalled();

        $this->subscriber->postLoad($eventArgs->reveal());
    }

    /**
     * @expectedException \AppBundle\Exception\UserNotFoundException
     * @expectedExceptionMessage User has not been authenticated yet
     */
    public function testPostLoadThrowsExceptionWhenTokenNotPresent()
    {
        $eventArgs = $this->prophesize('Doctrine\ORM\Event\LifecycleEventArgs');
        $entity = $this->prophesize('AppBundle\Entity\Game');

        $eventArgs->getEntity()->willReturn($entity);
        $this->tokenStorage->getToken()->willReturn(null);

        $this->subscriber->postLoad($eventArgs->reveal());
    }
}
