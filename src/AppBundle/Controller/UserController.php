<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Http\Headers;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;

class UserController extends FOSRestController
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param User $requestedUser
     * @return User
     * @Security("user.getId() === requestedUser.getId()")
     */
    public function getUserAction(User $requestedUser)
    {
        return $requestedUser;
    }

    /**
     * @param ParamFetcher $paramFetcher
     * @return Response
     *
     * @RequestParam(name="name", requirements="\S.*", allowBlank=false)
     */
    public function postUserAction(ParamFetcher $paramFetcher)
    {
        $user = new User();
        $user->setName($paramFetcher->get('name'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $view = $this
            ->routeRedirectView('api_v1_get_user', ['game' => $user->getId()])
            ->setHeader(Headers::API_KEY, sprintf('user:%d', $user->getId())) // @todo To be removed once there's real authentication with Api-Token
        ;

        return $this->handleView($view);
    }
}
