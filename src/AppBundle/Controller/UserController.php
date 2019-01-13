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
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Swagger\Annotations as SWG;
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
     * @Operation(
     *     tags={"User"},
     *     summary="Get user details",
     *     @SWG\Response(
     *         response="200",
     *         description="User data received",
     *         @SWG\Schema(
     *             type="object",
     *             ref=@Model(type=User::class)
     *         )
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="No access to the other user"
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="User not found"
     *     )
     * )
     *
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
     * @Operation(
     *     tags={"User"},
     *     summary="Create new user",
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="name",
     *                 type="string",
     *                 example="John Ship"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="201",
     *         description="User created"
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Incorrect 'name' provided"
     *     )
     * )
     *
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
     * @Operation(
     *     tags={"User"},
     *     summary="Update user",
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=false,
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="name",
     *                 type="string",
     *                 example="John Ship"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="204",
     *         description="User updated"
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Incorrect 'name' provided"
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="No access to user"
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="User not found"
     *     )
     * )
     *
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
