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
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
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
     * Example response:<pre>{"name": "John Player"}</pre>
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Get user details",
     *  section="User",
     *  output="AppBundle\Entity\User",
     *  statusCodes={
     *     200="User data received",
     *     403="No access to the other user",
     *     404="User not found",
     *  }
     * )
     * @Tag(expression="'user-' ~ requestedUser.getId()")
     * @Security("user.getId() === requestedUser.getId()")
     *
     * @param User $requestedUser
     * @return User
     */
    public function getUserAction(User $requestedUser)
    {
        return $requestedUser;
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Create new user",
     *  section="User",
     *  statusCodes={
     *     201="User created",
     *     400="Incorrect 'name' provided"
     *  }
     * )
     * @RequestParam(name="name", requirements=".*\S.*", allowBlank=false)
     *
     * @param ParamFetcher $paramFetcher
     * @return Response
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
     * @ApiDoc(
     *  resource=true,
     *  description="Update user",
     *  section="User",
     *  statusCodes={
     *     204="User updated",
     *     400="Incorrect 'name' provided",
     *     403="No access to user",
     *     404="User not found",
     *  }
     * )
     *
     * @Tag(expression="'user-' ~ requestedUser.getId()")
     * @Security("user.getId() === requestedUser.getId()")
     * @RequestParam(name="name", requirements=".*\S.*", allowBlank=false)
     *
     * @param ParamFetcher $paramFetcher
     * @param User $requestedUser
     */
    public function patchUserAction(ParamFetcher $paramFetcher, User $requestedUser)
    {
        $requestedUser->setName($paramFetcher->get('name'));

        $this->entityManager->persist($requestedUser);
        $this->entityManager->flush();
    }
}
