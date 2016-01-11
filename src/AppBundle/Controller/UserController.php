<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Http\Headers;
use AppBundle\Security\ApiKeyManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\HttpCacheBundle\Configuration\Tag;
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
     * @var ApiKeyManager
     */
    protected $apiKeyManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param ApiKeyManager $apiKeyManager
     */
    public function __construct(EntityManagerInterface $entityManager, ApiKeyManager $apiKeyManager)
    {
        $this->entityManager = $entityManager;
        $this->apiKeyManager = $apiKeyManager;
    }

    /**
     * @todo maybe rename user to player? (then player/other vs. user)
     *
     * @param User $requestedUser
     * @return User
     *
     * @Tag(expression="'user-' ~ requestedUser.getId()")
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

        $apiKey = $this->apiKeyManager->generateApiKeyForUser($user);
        $view = $this
            ->routeRedirectView('api_v1_get_user', ['requestedUser' => $user->getId()])
            ->setHeader(Headers::API_KEY, $apiKey)
        ;

        return $this->handleView($view);
    }

    /**
     * @param ParamFetcher $paramFetcher
     * @param User $requestedUser
     *
     * @Tag(expression="'user-' ~ requestedUser.getId()")
     * @Security("user.getId() === requestedUser.getId()")
     * @RequestParam(name="name", requirements="\S.*", allowBlank=false)
     */
    public function patchUserAction(ParamFetcher $paramFetcher, User $requestedUser)
    {
        $requestedUser->setName($paramFetcher->get('name'));

        $this->entityManager->persist($requestedUser);
        $this->entityManager->flush();
    }
}
