<?php

namespace App\Controller\Api;

use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Utils\API\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Sylius\Component\Core\Model\ShopUser;
use Sylius\Component\User\Model\User as BaseUser;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\Customer;
use JMS\SerializerBundle\JMSSerializerBundle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Utils\RefreshTokenManager;
use Gesdinet\JWTRefreshTokenBundle\Request as RefreshTokenRequest;
use App\Utils\FacebookVerificationManager;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;

class ApiController extends FOSRestController {

    private $customerRepository;
    private $orderRepository;
    private $refreshTokenMgr;
    private $facebookManager;

    public function __construct(RefreshTokenManager $refreshTokenMgr, UserPasswordEncoderInterface $passwordEncoder, FacebookVerificationManager $facebookManager) {
        $this->passwordEncoder = $passwordEncoder;
        $this->refreshTokenMgr = $refreshTokenMgr;
        $this->facebookManager = $facebookManager;
    }

    /**
     * post login
     * @Operation(
     *     summary="login and get token",
     *     description="login with username and password and return token",
     *     tags={"papa_ghanoug"},
     *     @SWG\Parameter(
     *         name="username",
     *         in="formData",
     *         type="string",
     *         description="user name"
     *    ),
     *     @SWG\Parameter(
     *         name="password",
     *         in="formData",
     *         type="string",
     *         description="password"
     *    ),
     *     @SWG\Response(
     *         response="200",
     *         description="On success",
     *     ),
     * )
     */
    public function postLoginAction(Request $request) {
////////////////////////////////////////////////////////////////////////////////////
//        $em = $this->getDoctrine()->getManager();
//        $products = $em->getRepository("Sylius\Component\Core\Model\Product")->findAll();
//        $view = $this->view(new ApiResponse(Response::HTTP_OK, ['products' => $products]), Response::HTTP_OK);
//        return $this->handleView($view);
////////////////////////////////////////////////////////////////////////////////////
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository("Sylius\Component\Core\Model\ShopUser")->findBy(array('username' => $username));
        if ($user) {
            $isPasswordValid = $this->passwordEncoder->isPasswordValid($user[0], $password);
            //\Doctrine\Common\Util\Debug::dump($isPasswordValid);
            if ($isPasswordValid) {
                $token = $this->get('lexik_jwt_authentication.jwt_manager')->create($user[0]);
                $refreshToken = $this->refreshTokenMgr->generateFromUser($user[0]);
                $view = $this->view(new ApiResponse(Response::HTTP_OK, ['token' => $token, 'refresh_token' => $refreshToken]), Response::HTTP_OK);
                return $this->handleView($view);
            } else {
                $view = $this->view(new ApiResponse(Response::HTTP_FORBIDDEN, ['error' => "invalid username or password"]), Response::HTTP_FORBIDDEN);
                return $this->handleView($view);
            }
        } else {
            throw $this->createNotFoundException('User credentials are wrong');
        }
    }

////////////////////////////////////////////////////////////////////////////////////

    /**
     * Get any action any any any 
     * @Operation(
     *     summary="Get any",
     *     description="Get anyyyy for user",
     *     tags={"papa_ghanoug"},
     *     @SWG\Response(
     *         response="200",
     *         description="On success",
     *     ),
     * )
     */
    public function getAnyAction(Request $request) {
        $data = ["ok"]; // get data, in this case list of users.
        $view = $this->view($data, 200)
                ->setTemplate("MyBundle:Users:getUsers.html.twig")
                ->setTemplateVar('users')
        ;
        return $this->handleView($view);
    }

////////////////////////////////////////////////////////////////////////////////////

    /**
     * Get user orders
     * @Operation(
     *     summary="Get all user orders",
     *     description="Get orders for a specific user given his token",
     *     tags={"papa_ghanoug"},
     *     @SWG\Response(
     *         response="200",
     *         description="On success",
     *     ),
     * )
     */
    public function getUserOrdersAction(Request $request) {
        $user = $this->getUser();
        $userEmail = $user->getEmail();
        $userId = $user->getId();
        $em = $this->getDoctrine()->getManager();
        $orders = $em->getRepository("Sylius\Component\Core\Model\Order")->findBy(['customer' => $user]);
        $view = $this->view(new ApiResponse(Response::HTTP_OK, ['orders' => $orders]), Response::HTTP_OK);
        return $this->handleView($view);
    }

////////////////////////////////////////////////////////////////////////////////////

    /**
     * Get user profile
     * @Operation(
     *     summary="Get user profile",
     *     description="Get user profile given his token",
     *     tags={"papa_ghanoug"},
     *     @SWG\Response(
     *         response="200",
     *         description="On success",
     *     ),
     * )
     */
    public function getUserProfileAction(Request $request) {
        $user = $this->getUser();
        $view = $this->view(new ApiResponse(Response::HTTP_OK, $user), Response::HTTP_OK);
        $view->getContext()->setGroups(['api_response', 'profile']);
        return $this->handleView($view);
    }

    ////////////////////////////////////////////////////////////////////////////////////

    /**
     * login with facebook
     * @Operation(
     *     summary="social login with facebook",
     *     description="Post facebook login",
     *     tags={"papa_ghanoug"},
     *     @SWG\Response(
     *         response="200",
     *         description="On success",
     *     ),
     * )
     */
    public function postFacebookLoginAction(Request $request) {
        $userInfo = [];
        // handle facebook token
        $facebookToken = $request->get('facebookToken');
        $facebookToken = "EAAer2ruGIY8BAL69x9WmkUZBlqRvqUFxf9M1VjZBWiMs7wKs37As6uz1wTApH49neha6OSssWiKLPnYBNGjWEVpDsGOk7McoOyv163ZAvkKZBqMGCQhszmAMX90dlXsXgf0JhD4cbHZBZC7AQzmrVnCzxjNRE4HT3v88E0cquGxBUZC8yrlMO1flxQ37AwDFn8hdAT8ioeNcAZDZD";
        $facebookId = null;
        if ($facebookToken) {
            $isValid = $this->facebookManager->verifySocialToken($facebookToken);
            if (!$isValid) {
                return $this->handleView($this->view(new ApiResponse($this->facebookManager->getStatusCode(), null, $this->get('translator')->trans($this->facebookManager->getErrorMessage())), $this->facebookManager->getStatusCode()));
            }
            $facebookId = $this->facebookManager->getSocialId();
            $userInfo = $this->facebookManager->getUserInfo();
        }
        $view = $this->view(new ApiResponse(Response::HTTP_OK, ['userInfo' => $userInfo]), Response::HTTP_OK);
        return $this->handleView($view);
    }

////////////////////////////////////////////////////////////////////////////////////

    /**
     * Get new token/refresh values.
     * Using this API renews the 6 Months period of the refresh token
     * 
     * @Rest\Post("/token/refresh")
     * tags={"papa_ghanoug"},
     * @Operation(
     *     summary="Refresh user token",
     *     @SWG\Parameter(
     *         name="refresh_token",
     *         in="formData",
     *         description="refresh token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="On success, return token.",
     *         @SWG\Items(
     *              type="object",
     *              @SWG\Property(property="token", type="string"),
     *              @SWG\Property(property="refresh_token", type="string")
     *        )
     *     )
     * )
     */
    public function postRefreshTokenAction(Request $request) {
        $refreshToken = $request->get('refresh_token');
        $response = $httpClient->request(self::REQUEST_METHOD, self::APP_REQUEST_URL . "?refresh_token={$refreshToken}");

        var_dump($response);
        $this->statusCode = $response->getStatusCode();

        $body = $response->getBody()->getContents();
        $body = json_decode($body);

        var_dump($body);
        $view = $this->view(new ApiResponse(Response::HTTP_OK, ['body' => $body]), Response::HTTP_OK);
        return $this->handleView($view);
    }

    ////////////////////////////////////////////////////////////////////////////////////

    /**
     * Post Order.
     * @Operation(
     *     summary="Post new order",
     *     tags={"papa_ghanoug"},
     *     @SWG\Response(
     *         response="200",
     *         description="On success, return token.",
     *     )
     * )
     */
    public function postOrderAction(Request $request) {
        /** @var FactoryInterface $order */
        $orderFactory = $this->container->get('sylius.factory.order');

        /** @var OrderInterface $order */
        $order = $orderFactory->createNew();

        /** @var ChannelInterface $channel */
        $channel = $this->container->get('sylius.context.channel')->getChannel();
        $order->setChannel($channel);


        /** @var string $localeCode */
        $localeCode = $this->container->get('sylius.context.locale')->getLocaleCode();
        $order->setLocaleCode($localeCode);


        $currencyCode = $this->container->get('sylius.context.currency')->getCurrencyCode();
        $order->setCurrencyCode($currencyCode);

        $user = $this->getUser();
        $userEmail = $user->getEmail();

        /** @var CustomerInterface $customer */
        $customer = $this->container->get('sylius.repository.customer')->findOneBy(['email' => $userEmail]);
        $order->setCustomer($customer);

        /** @var ProductVariantInterface $variant */
        $variant = $this->container->get('sylius.repository.product_variant')->findOneBy(['id' => 9]);

        // Instead of getting a specific variant from the repository
        // you can get the first variant of off a product by using $product->getVariants()->first()
        // or use the **VariantResolver** service - either the default one or your own.
        // The default product variant resolver is available at id - 'sylius.product_variant_resolver.default'

        /** @var OrderItemInterface $orderItem */
        $orderItem = $this->container->get('sylius.factory.order_item')->createNew();
        $orderItem->setVariant($variant);


        $this->container->get('sylius.order_item_quantity_modifier')->modify($orderItem, 1);

        $order->addItem($orderItem);

        $this->container->get('sylius.order_processing.order_processor')->process($order);
        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = $this->container->get('sylius.repository.order');

        $orderRepository->add($order);


        $view = $this->view(new ApiResponse(Response::HTTP_OK, ['savedOrder' => $order]), Response::HTTP_OK);
        return $this->handleView($view);
    }

////////////////////////////////////////////////////////////////////////////////////

    /**
     * Get Product Details
     * @Rest\Get("/product_details/{id}")
     * @Operation(
     *     summary="Get product",
     *     description="Get product",
     *     tags={"papa_ghanoug"},
     *     @SWG\Response(
     *         response="200",
     *         description="On success",
     *         @SWG\Schema(
     *              type="array",
     *              @Model(type=product::class, groups={"product_details"})
     *         ) 
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     * )
     */
    public function getProductAction(Request $request, $id) {
        $product = $this->container->get('sylius.repository.product')->findOneBy(['id' => $id]);

        //$view = $this->view(new ApiResponse(Response::HTTP_OK, $product), Response::HTTP_OK);
        $view = $this->view($product);

       $view->getContext()->setGroups(['Detailed']);

        return $this->handleView($view);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * post register
     * @Operation(
     *     summary="register with info",
     *     description="register with data and then login with username and password and return token",
     *     tags={"papa_ghanoug"},
     *     @SWG\Parameter(
     *         name="username",
     *         in="formData",
     *         type="string",
     *         description="user name"
     *    ),
     *    @SWG\Parameter(
     *         name="email",
     *         in="formData",
     *         type="string",
     *         description="email"
     *    ),
     *     @SWG\Parameter(
     *         name="password",
     *         in="formData",
     *         type="string",
     *         description="password"
     *    ),
     *    @SWG\Parameter(
     *         name="birthday",
     *         in="formData",
     *         type="string",
     *         description="birthday"
     *    ),
     *    @SWG\Parameter(
     *         name="gender",
     *         in="formData",
     *         type="string",
     *         description="Gender"
     *    ),
     *     @SWG\Response(
     *         response="200",
     *         description="On success",
     *     ),
     * )
     */
    public function postRegisterAction(Request $request) {
        $username = $request->request->get('username');
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $birthday = $request->request->get('birthday');
        $gender = $request->request->get('gender');



        $customer = $this->container->get('sylius.repository.customer')->findOneBy(['email' => $email]);
        if ($customer) {
            $view = $this->view(new ApiResponse(Response::HTTP_FORBIDDEN, ['error' => "customer already exist or duplicate email"]), Response::HTTP_FORBIDDEN);
            return $this->handleView($view);
        }


        /** @var CustomerInterface $customer */
        $customer = $this->container->get('sylius.factory.customer')->createNew();
        $customer->setEmail($email);
        $customer->setBirthday($birthday);
        $customer->setGender($gender);
        $this->container->get('sylius.repository.customer')->add($customer);




        if ($customer) {
            /** @var ShopUserInterface $user */
            $user = $this->container->get('sylius.factory.shop_user')->createNew();
            // Now let's find a Customer by their e-mail:
            /** @var CustomerInterface $customer */
            $customer = $this->container->get('sylius.repository.customer')->findOneBy(['email' => $email]);
            // and assign it to the ShopUser
            $user->setCustomer($customer);
            $user->setPlainPassword($password);
            $user->setUserName($username);
            $user->setEnabled(true);
            $this->container->get('sylius.repository.shop_user')->add($user);
            $view = $this->view(new ApiResponse(Response::HTTP_OK, ['user' => $user]), Response::HTTP_OK);
            return $this->handleView($view);
        } else {
            $view = $this->view(new ApiResponse(Response::HTTP_FORBIDDEN, ['error' => "customer not created"]), Response::HTTP_FORBIDDEN);
            return $this->handleView($view);
        }
    }

}
