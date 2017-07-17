<?php

namespace Tests\AppBundle\EventSubscriber;

use AppBundle\Entity\Game;
use AppBundle\Entity\User;
use AppBundle\EventSubscriber\EntitySubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        $this->tokenStorage = $this->prophesize(TokenStorage::class);
        $this->validator = $this->prophesize(ValidatorInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->subscriber = new EntitySubscriber($this->tokenStorage->reveal(), $this->validator->reveal(), $this->logger->reveal());
    }

    public function testPreUpdate()
    {
        $eventArgs = $this->prophesize(PreUpdateEventArgs::class);
        $eventArgs->getEntity()->willReturn('entity');

        $this->validator->validate('entity', null, ['update'])->shouldBeCalled();
        $this->subscriber->preUpdate($eventArgs->reveal());
    }

    public function testPrePersist()
    {
        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getEntity()->willReturn('entity');

        $this->validator->validate('entity')->shouldBeCalled();
        $this->subscriber->prePersist($eventArgs->reveal());
    }

    public function testPostLoadSetsLoggerForLoggerAwareEntities()
    {
        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $entity = $this->prophesize(LoggerAwareInterface::class);
        $eventArgs->getEntity()->willReturn($entity);

        $entity->setLogger($this->logger)->shouldBeCalled();

        $this->subscriber->postLoad($eventArgs->reveal());
    }

    public function testPostLoadSetsLoggedUser()
    {
        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $entity = $this->prophesize(Game::class);
        $token = $this->prophesize(TokenInterface::class);
        $user = $this->prophesize(User::class);

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
        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $entity = $this->prophesize(Game::class);

        $eventArgs->getEntity()->willReturn($entity);
        $this->tokenStorage->getToken()->willReturn(null);

        $this->subscriber->postLoad($eventArgs->reveal());
    }
}
