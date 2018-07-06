<?php

namespace Btc\FrontendApiBundle\Controller;

use Btc\CoreBundle\Entity\Activity;
use Btc\CoreBundle\Entity\Bank;
use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\PhoneVerification;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Verification;
use Btc\CoreBundle\Entity\Wallet;
use Btc\FrontendApiBundle\Classes\RestSecurity;
use Btc\FrontendApiBundle\Events\AccountActivityEvents;
use Btc\FrontendApiBundle\Events\UserActivityEvent;
use Btc\FrontendApiBundle\Exception\Rest\AlreadyExistsException;
use Btc\FrontendApiBundle\Exception\Rest\InvalidCredentialsException;
use Btc\FrontendApiBundle\Exception\Rest\NotEnoughMoneyException;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\FrontendApiBundle\Exception\Rest\NotValidDataException;
use Btc\FrontendApiBundle\Exception\Rest\TwoFactorAuthDisabledException;
use Btc\FrontendApiBundle\Exception\Rest\UnknownErrorException;
use Btc\FrontendApiBundle\Exception\Rest\UserBlockedException;
use Btc\FrontendApiBundle\Exception\Rest\UserNotFoundException;
use Btc\FrontendApiBundle\Exception\Rest\TooOftenAddressRequestException;
use Btc\FrontendApiBundle\Exception\Rest\OptimisticLockException;
use Btc\FrontendApiBundle\Pagination\ActivitiesActionFilter;
use Btc\FrontendApiBundle\Pagination\ActivitiesDateRangeFilter;
use Btc\PaginationBundle\Filters\PageLimitFilter;
use Btc\PaginationBundle\Target;
use Btc\TransferBundle\Gateway\Coin\Exceptions\NewAddressLimitReachedException;
use Exmarkets\PaymentCoreBundle\Gateway\Coin\CoinApiInterface;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Request\ParamFetcherInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Email;

class UserController extends FOSRestController
{
    /**
     * Registers user.
     *
     * ### Request URL example ###
    POST /api/v1/users/register
    body: {"email":"123@gmail.com","newsletter":true}
     * ### Success response example ###
     *     {
     *       "isSuccess": true
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "ALREADY_EXISTS"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Registers user",
     *   input = "Btc\FrontendApiBundle\Form\RegisterType",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when something is wrong"
     *   },
     *   section = "User"
     * )
     *
     * @Annotations\Post("/users/register")
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws AlreadyExistsException when user already exists
     */
    public function registerUserAction(Request $request)
    {
        $email = $request->get('email');

        $userService = $this->get('rest.service.user');

        if ($user = $userService->getOneBy(compact('email'))) {
            throw new AlreadyExistsException();
        }

        $user = $userService->processRegisterForm($request);

        $pinService = $this->get('rest.service.pin');

        $sourcePin = $pinService->generate(7);
        $encodedPin = $pinService->encodePin($sourcePin, $user->getSalt());

        $user->setPin($encodedPin);

        $user = $userService->patch($user);

        $user->setPin($sourcePin);

        $this->get('rest.service.mailer')->sendNewPinMessage($user);

        return $this->handleView(
            $this->view([
                'isSuccess' => boolval($user),
            ], Response::HTTP_OK)
        );
    }

    /**
     * Send HotP to the user.
     *
     * ### Request URL example ###
     * GET /api/v1/users/send_pin
     * ### Success response example ###
     *     {
     *       "isSuccess": true
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "TWO_FACTOR_AUTH_DISABLED"
     *     }
     *     {
     *       "status": 500,
     *       "error": "UNKNOWN_ERROR"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Send HotP to the user",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned something is wrong",
     *     Response::HTTP_INTERNAL_SERVER_ERROR = "Returned something is wrong",
     *   },
     *   section = "User",
     *   authentication = true
     * )
     *
     * @Annotations\Get("/users/send_pin")
     *
     * @return Response
     *
     * @throws TwoFactorAuthDisabledException when user has not hotp
     * @throws UnknownErrorException          when unknown error
     */
    public function sendHotpAction()
    {
        $user = $this->getUser();

        if (!$user->hasHOTP()) {
            throw new TwoFactorAuthDisabledException();
        }

        try {
            $this->get('rest.service.phone')->sendHotp($user);
            $this->get('em')->flush();
        } catch (\Exception $e) {
            throw new UnknownErrorException();
        }

        return $this->handleView($this->view([
            'isSuccess' => true,
        ]));
    }

    /**
     * Logouts user.
     *
     * ### Request URL example ###
     * POST /api/v1/users/logout
     * ### Success response example ###
     *     {
     *       "isLogout": true
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "message",
     *       "isLogout": false
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Logouts user",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned something is wrong"
     *   },
     *   section = "User",
     *   authentication = true
     * )
     *
     * @Annotations\Post("/users/logout")
     *
     * @return Response
     */
    public function logoutAction()
    {
        try {
            $user = $this->getUser();
            $user->setToken('');

            $this->get('rest.service.user')
                ->patch($user);

            $this->get('request')->getSession()->invalidate();
            $this->get('security.token_storage')->setToken(null);

            $view = $this->view([
                'isLogout' => true,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            $view = $this->view([
                'isLogout' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->handleView($view);
    }

    /**
     * Updates user information.
     *
     * ### Request URL example ###
    POST /api/v1/users/update
    body: {"personalInfo":{"firstName":"1","lastName":"2","address":"3","zipCode":"4","city":"5","country":"6","birthDate":"2018-11-11","phone":"7"},"businessInfo":{"companyName":"11","vatId":"22","registrationNumber":"33","country":"44","state":"55","city":"66","street":"77"}}
     * ### Success response example ###
     *     {
     *       "user":  {
     *          "id": 1,
     *          "email": "e.dyukarev@besk.com",
     *          "verification": {
     *              "business_info": {
     *                  "company_name": "77",
     *                  "vat_id": "88",
     *                  "registration_number": "99",
     *                  "country": {
     *                      "id": 6,
     *                      "name": "ANDORRA",
     *                      "iso2": "AD",
     *                      "iso3": "AND"
     *                  },
     *                  "state": "2",
     *                  "city": "21",
     *                  "street": "3",
     *                  "building": "4",
     *                  "zip_code": "5",
     *                  "office_number": "6",
     *                  "company_details1": {
     *                      "name": "test1.png"
     *                  },
     *                  "status": "2"
     *              },
     *              "personal_info": {
     *                  "country": {
     *                      "id": 66,
     *                      "name": "EGYPT",
     *                      "iso2": "EG",
     *                      "iso3": "EGY"
     *                  },
     *                  "phone": "+375292138500",
     *                  "address": "33",
     *                  "city": "55",
     *                  "zip_code": "44",
     *                  "first_name": "12",
     *                  "last_name": "22",
     *                  "status": "2",
     *                  "id_photo": {
     *                      "name": "2.png"
     *                  },
     *                  "residence_proof": {
     *                      "name": "3.png"
     *                  },
     *                  "id_back_side": {
     *                      "name": "1.png"
     *                  }
     *              },
     *              "created_at": "2018-01-17T12:57:33+00:00"
     *          },
     *          "token": "",
     *          "fee_set": {
     *              "name": "Enterprise",
     *              "type": 3,
     *              "fees": [{
     *                  "buy_percent": "0.15000000",
     *                  "sell_percent": "0.15000000",
     *                  "market_id": 1
     *              }]
     *          }
     *      },
     *      "openOrders": [],
     *      "wallets": [{
     *          "id": 1,
     *          "currency": {
     *              "id": 1,
     *              "code": "USD",
     *              "sign": "$",
     *              "format": 2,
     *              "crypto": false
     *          },
     *          "balance": 544424.86699632,
     *          "reserved": 0,
     *          "total": 544424.86699632
     *      }]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "VALIDATION_ERROR"
     *     }
     *     {
     *       "status": 403,
     *       "error": "VERIFICATION_PENDING"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Updates user information",
     *   output = {
     *     "class" = "Btc\CoreBundle\Entity\Verification",
     *     "groups" = "api",
     *     "parsers"={"Nelmio\ApiDocBundle\Parser\JmsMetadataParser"}
     *   },
     *   input = "Btc\FrontendApiBundle\Form\UserVerificationType",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned something is wrong",
     *     Response::HTTP_FORBIDDEN = "Returns when verification in pending status"
     *   },
     *   section = "User",
     *   authentication = true
     * )
     *
     * @Annotations\Post("/users/update")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function updateAction(Request $request)
    {
        $user = $this->getUser();

        $verification = $user->getVerification();

        if (!$verification instanceof Verification) {
            $verification = $this->get('rest.repository.verification')->createVerification($user);
        }

        $userService = $this->get('rest.service.user');

        $user = $userService->processUpdateUserProfileForm($request, $verification);

        return $this->handleView(
            $userService->getUserView($user)
        );
    }

    /**
     * Gets user info.
     *
     * ### Request URL example ###
     * GET /api/v1/users/info
     * ### Success response example ###
     *     {
     *       "user":  {
     *          "id": 1,
     *          "email": "e.dyukarev@besk.com",
     *          "verification": {
     *              "business_info": {
     *                  "company_name": "77",
     *                  "vat_id": "88",
     *                  "registration_number": "99",
     *                  "country": {
     *                      "id": 6,
     *                      "name": "ANDORRA",
     *                      "iso2": "AD",
     *                      "iso3": "AND"
     *                  },
     *                  "state": "2",
     *                  "city": "21",
     *                  "street": "3",
     *                  "building": "4",
     *                  "zip_code": "5",
     *                  "office_number": "6",
     *                  "company_details1": {
     *                      "name": "test1.png"
     *                  },
     *                  "status": "2"
     *              },
     *              "personal_info": {
     *                  "country": {
     *                      "id": 66,
     *                      "name": "EGYPT",
     *                      "iso2": "EG",
     *                      "iso3": "EGY"
     *                  },
     *                  "phone": "+375292138500",
     *                  "address": "33",
     *                  "city": "55",
     *                  "zip_code": "44",
     *                  "first_name": "12",
     *                  "last_name": "22",
     *                  "status": "2",
     *                  "id_photo": {
     *                      "name": "2.png"
     *                  },
     *                  "residence_proof": {
     *                      "name": "3.png"
     *                  },
     *                  "id_back_side": {
     *                      "name": "1.png"
     *                  }
     *              },
     *              "created_at": "2018-01-17T12:57:33+00:00"
     *          },
     *          "token": "",
     *          "fee_set": {
     *              "name": "Enterprise",
     *              "type": 3,
     *              "fees": [{
     *                  "buy_percent": "0.15000000",
     *                  "sell_percent": "0.15000000",
     *                  "market_id": 1
     *              }]
     *          }
     *       },
     *       "openOrders": [{
     *              "id": 1,
     *              "market": {
     *                  "id": 1,
     *                  "slug": "btc-usd",
     *                  "currency": {
     *                      "id": 2,
     *                      "code": "BTC",
     *                      "sign": "฿",
     *                      "format": 8,
     *                      "crypto": true
     *                  },
     *                  "with_currency": {
     *                      "id": 1,
     *                      "code": "USD",
     *                      "sign": "$",
     *                      "format": 2,
     *                      "crypto": false
     *                  },
     *                  "name": "BTC-USD"
     *              },
     *              "in_wallet": {
     *                  "id": 1,
     *                  "currency": {
     *                      "id": 1,
     *                      "code": "USD",
     *                      "sign": "$",
     *                      "format": 2,
     *                      "crypto": false
     *                  },
     *                  "balance": 10044.24498674,
     *                  "reserved": 3767.39,
     *                  "total": 6273.85498674
     *              },
     *              "out_wallet": {
     *                  "id": 2,
     *                  "currency": {
     *                      "id": 2,
     *                      "code": "BTC",
     *                      "sign": "฿",
     *                      "format": 8,
     *                      "crypto": true
     *                  },
     *                  "balance": 36.5,
     *                  "reserved": 1,
     *                  "total": 35.5
     *              },
     *              "created_at": "2018-01-05T00:00:00+00:00",
     *              "updated_at": "2018-01-20T18:15:59+00:00",
     *              "completed_at": "2018-01-12T11:24:47+00:00",
     *              "status": "1",
     *              "current_amount": 1,
     *              "price": 1,
     *              "fee_percent": 1,
     *              "fee_amount_reserved": 1,
     *              "fee_amount_taken": 1,
     *              "type": "1",
     *              "start_quantity": 1,
     *              "transactions": [{
     *                  "id": 1,
     *                  "amount": 1,
     *                  "fee": 1,
     *                  "price": 1,
     *                  "status": 1,
     *                  "type": "maker",
     *                  "platform": "EXM",
     *                  "executed_at": "2017-11-24T04:48:00+00:00"
     *              }],
     *              "side": "SELL",
     *              "reserve_total": 1,
     *              "reserve_spent": 1,
     *              "is_buy": false,
     *              "quantity": 1,
     *              "market_id": 1
     *      }],
     *      "wallets": [{
     *          "id": 1,
     *          "currency": {
     *              "id": 1,
     *              "code": "USD",
     *              "sign": "$",
     *              "format": 2,
     *              "crypto": false
     *          },
     *          "balance": 544424.86699632,
     *          "reserved": 0,
     *          "total": 544424.86699632
     *      }]
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets user info",
     *   output = {
     *     "class" = "Btc\CoreBundle\Entity\Order",
     *     "groups" = "api"
     *   },
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful"
     *   },
     *   section = "User",
     *   authentication = true
     * )
     *
     * @Annotations\Get("/users/info")
     *
     * @return Response
     */
    public function getUserInfoAction()
    {
        $view = $this->get('rest.service.user')->getUserView($this->getUser());

        return $this->handleView($view);
    }

    /**
     * Prelogins user by email.
     *
     * ### Request URL example ###
    POST /api/v1/users/prelogin
    body: {"email":"1@tut.by"}
     * ### Success response example ###
     *     {
     *       "status": true
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "INVALID_USERNAME_OR_PASSWORD",
     *     }
     *     {
     *       "status": 400,
     *       "error": "VALIDATION_ERROR",
     *     }
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA",
     *     }
     *     {
     *       "status": 400,
     *       "error": "USER_BLOCKED",
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Prelogins user by email",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when something is wrong"
     *   },
     *   section = "User"
     * )
     *
     * @Annotations\Post("/users/prelogin")
     *
     * @Annotations\RequestParam(name="email", requirements="\S+")
     *
     * @param ParamFetcher $fetcher
     *
     * @return Response
     *
     * @throws NotValidDataException
     * @throws UserBlockedException
     * @throws InvalidCredentialsException
     */
    public function postUserPreLoginByEmailAction(ParamFetcher $fetcher)
    {
        $email = $fetcher->get('email');

        $isNotEmail = $this->get('validator.builder')->getValidator()
            ->validate($email, new Email())
            ->count();

        if ($isNotEmail) {
            throw new NotValidDataException();
        }

        $userService = $this->get('rest.service.user');

        $user = $userService->getOneBy(['email' => $email]);

        if ($user instanceof User) {
            if ($user->hasRole(User::BLOCKED)) {
                throw new UserBlockedException();
            }

            $key = sprintf(RestSecurity::CNT_REQUEST_GENERATE_PIN_KEY, $user->getId());

            $redisService = $this->get('rest.redis');

            if ($redisService->get($key) >= RestSecurity::ATTEMPT_GENERATE_PIN) {
                $userService->blockedUser($user);

                throw new UserBlockedException();
            }

            $pinService = $this->get('rest.service.pin');

            $sourcePin = $pinService->generate(7);
            $encodedPin = $pinService->encodePin($sourcePin, $user->getSalt());

            $user->setPin($encodedPin);

            $userService->patch($user);

            $user->setPin($sourcePin);

            $redisService->incr($key);

            $this->get('rest.service.mailer')->sendNewPinMessage($user);
        } else {
            throw new InvalidCredentialsException();
        }

        return $this->handleView(
            $this->view([
                'status' => boolval($user),
            ], Response::HTTP_OK)
        );
    }

    /**
     * Logins user by email and pin.
     *
     * ### Request URL example ###
    POST /api/v1/users/login
    body: {"email":"1@tut.by","pin":"123"}
     * ### Success response example ###
     *     {
     *       "user": {
     *          "id": 28,
     *          "email": "test@gmail.com",
     *          "token": "123abc",
     *          "fee_set": {
     *              "name": "Starter",
     *              "type": 3,
     *              "fees": [{
     *                  "buy_percent": "0.20000000",
     *                  "sell_percent": "0.20000000",
     *                  "market_id": 1
     *              }]
     *          }
     *       },
     *       "open_orders": [{
     *              "id": 1,
     *              "market": {
     *                  "id": 1,
     *                  "slug": "btc-usd",
     *                  "currency": {
     *                      "id": 2,
     *                      "code": "BTC",
     *                      "sign": "฿",
     *                      "format": 8,
     *                      "crypto": true
     *                  },
     *                  "with_currency": {
     *                      "id": 1,
     *                      "code": "USD",
     *                      "sign": "$",
     *                      "format": 2,
     *                      "crypto": false
     *                  },
     *                  "name": "BTC-USD"
     *              },
     *              "in_wallet": {
     *                  "id": 1,
     *                  "currency": {
     *                      "id": 1,
     *                      "code": "USD",
     *                      "sign": "$",
     *                      "format": 2,
     *                      "crypto": false
     *                  },
     *                  "balance": 10044.24498674,
     *                  "reserved": 3767.39,
     *                  "total": 6273.85498674
     *              },
     *              "out_wallet": {
     *                  "id": 2,
     *                  "currency": {
     *                      "id": 2,
     *                      "code": "BTC",
     *                      "sign": "฿",
     *                      "format": 8,
     *                      "crypto": true
     *                  },
     *                  "balance": 36.5,
     *                  "reserved": 1,
     *                  "total": 35.5
     *              },
     *              "created_at": "2018-01-05T00:00:00+00:00",
     *              "updated_at": "2018-01-20T18:15:59+00:00",
     *              "completed_at": "2018-01-12T11:24:47+00:00",
     *              "status": "1",
     *              "current_amount": 1,
     *              "price": 1,
     *              "fee_percent": 1,
     *              "fee_amount_reserved": 1,
     *              "fee_amount_taken": 1,
     *              "type": "1",
     *              "start_quantity": 1,
     *              "transactions": [{
     *                  "id": 1,
     *                  "amount": 1,
     *                  "fee": 1,
     *                  "price": 1,
     *                  "status": 1,
     *                  "type": "maker",
     *                  "platform": "EXM",
     *                  "executed_at": "2017-11-24T04:48:00+00:00"
     *              }],
     *              "side": "SELL",
     *              "reserve_total": 1,
     *              "reserve_spent": 1,
     *              "is_buy": false,
     *              "quantity": 1,
     *              "market_id": 1
     *      }],
     *      "wallets": [{
     *          "id": 1,
     *          "currency": {
     *              "id": 1,
     *              "code": "USD",
     *              "sign": "$",
     *              "format": 2,
     *              "crypto": false
     *          },
     *          "balance": 544424.86699632,
     *          "reserved": 0,
     *          "total": 544424.86699632
     *      }]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA",
     *     }
     *     {
     *       "status": 400,
     *       "error": "INVALID_USERNAME_OR_PASSWORD",
     *     }
     *     {
     *       "status": 404,
     *       "error": "USER_NOT_FOUND",
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Logins user by email and pin",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when something is wrong",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "User",
     *   authentication = true
     * )
     *
     * @Annotations\Post("/users/login")
     *
     * @Annotations\RequestParam(name="email", requirements="\S+")
     * @Annotations\RequestParam(name="pin", requirements="\S+")
     *
     * @param ParamFetcher $fetcher
     * @param Request $request
     *
     * @return Response
     *
     * @throws UserNotFoundException
     * @throws InvalidCredentialsException
     */
    public function postUserLoginByEmailPinAction(ParamFetcher $fetcher, Request $request)
    {
        $email = $fetcher->get('email');
        $pin = $fetcher->get('pin');

        $userService = $this->get('rest.service.user');

        $user = $userService->getOneBy([
            'email' => $email,
        ]);

        if (!$user) {
            throw new UserNotFoundException();
        }

        if ($this->getParameter('rest_mail_pin_mode')) {
            if (!$this->get('rest.service.pin')->isPinValid($user->getPin(), $pin, $user->getSalt())) {
                throw new InvalidCredentialsException();
            }
        }

        $token = $this->get('rest.service.auth')
            ->getAuthToken($user->getId());
        $user->setToken($token);

        $userObject = $user;    // for not updating

        $key = sprintf(RestSecurity::CNT_REQUEST_GENERATE_PIN_KEY, $user->getId());
        $this->get('rest.redis')->del($key);

        $this->get('event_dispatcher')->dispatch(
            AccountActivityEvents::CUSTOM_LOGIN,
            new UserActivityEvent($userObject, $request)
        );

        return $this->handleView(
            $userService->getUserView($user)
        );
    }

    /**
     * Refreshes user token.
     *
     * ### Request URL example ###
    PATCH /api/v1/users/tokens/statuses/refresh
    body: {"token": current_user_token}
     * ### Success response example ###
     *     {
     *          "token": new_user_token
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "INVALID_USERNAME_OR_PASSWORD",
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Refreshes user token",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when something is wrong"
     *   },
     *   section = "User",
     *   authentication = true
     * )
     *
     * @Annotations\Patch("/users/tokens/statuses/refresh")
     *
     * @return Response
     *
     * @throws UserNotFoundException
     * @throws InvalidCredentialsException
     */
    public function refreshUserTokenAction()
    {
        $user = $this->getUser();

        $token = $this->get('rest.service.auth')
            ->getAuthToken($user->getId());

        return $this->handleView(
            $this->view([
                'token' => $token,
            ], Response::HTTP_OK)
        );
    }

    /**
     * Gets user by user id.
     *
     * ### Request URL example ###
     * GET /api/v1/users/1
     * ### Success response example ###
     *     {
     *       "user":  {
     *          "id": 1,
     *          "email": "e.dyukarev@besk.com",
     *          "verification": {
     *              "business_info": {
     *                  "company_name": "77",
     *                  "vat_id": "88",
     *                  "registration_number": "99",
     *                  "country": {
     *                      "id": 6,
     *                      "name": "ANDORRA",
     *                      "iso2": "AD",
     *                      "iso3": "AND"
     *                  },
     *                  "state": "2",
     *                  "city": "21",
     *                  "street": "3",
     *                  "building": "4",
     *                  "zip_code": "5",
     *                  "office_number": "6",
     *                  "company_details1": {
     *                      "name": "test1.png"
     *                  },
     *                  "status": "2"
     *              },
     *              "personal_info": {
     *                  "country": {
     *                      "id": 66,
     *                      "name": "EGYPT",
     *                      "iso2": "EG",
     *                      "iso3": "EGY"
     *                  },
     *                  "phone": "+375292138500",
     *                  "address": "33",
     *                  "city": "55",
     *                  "zip_code": "44",
     *                  "first_name": "12",
     *                  "last_name": "22",
     *                  "status": "2",
     *                  "id_photo": {
     *                      "name": "2.png"
     *                  },
     *                  "residence_proof": {
     *                      "name": "3.png"
     *                  },
     *                  "id_back_side": {
     *                      "name": "1.png"
     *                  }
     *              },
     *              "created_at": "2018-01-17T12:57:33+00:00"
     *          },
     *          "token": "",
     *          "fee_set": {
     *              "name": "Enterprise",
     *              "type": 3,
     *              "fees": [{
     *                  "buy_percent": "0.15000000",
     *                  "sell_percent": "0.15000000",
     *                  "market_id": 1
     *              }]
     *          }
     *      },
     *      "openOrders": [],
     *      "wallets": [{
     *          "id": 1,
     *          "currency": {
     *              "id": 1,
     *              "code": "USD",
     *              "sign": "$",
     *              "format": 2,
     *              "crypto": false
     *          },
     *          "balance": 544424.86699632,
     *          "reserved": 0,
     *          "total": 544424.86699632
     *      }]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA",
     *     }
     *     {
     *       "status": 404,
     *       "error": "USER_NOT_FOUND",
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets user by user id",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "User",
     *   authentication = true
     * )
     *
     * @Annotations\Get("/users/{userId}", requirements = { "userId" = "\d+" })
     *
     * @param int $userId
     *
     * @return Response
     *
     * @throws NotFoundException when items do not exist
     */
    public function getUserByUserIdAction($userId)
    {
        $userService = $this->get('rest.service.user');

        if ($user = $userService->get($userId)) {
            return $this->handleView(
                $userService->getUserView($user)
            );
        }

        throw new UserNotFoundException();
    }

    /**
     * Gets user wallet balance.
     *
     * ### Request URL example ###
     * GET /api/v1/wallets/balance
     * ### Success response example ###
     *     {
     *      "wallets": [{
     *          "id": 1,
     *          "currency": {
     *              "id": 1,
     *              "code": "USD",
     *              "sign": "$",
     *              "format": 2,
     *              "crypto": false
     *          },
     *          "balance": 544424.86699632,
     *          "reserved": 0,
     *          "total": 544424.86699632
     *      }]
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets user wallet balance",
     *   output = {
     *     "class" = "Btc\CoreBundle\Entity\Wallet",
     *     "groups" = "api"
     *   },
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Wallet",
     *   authentication = true
     * )
     *
     * @Annotations\Get("/wallets/balance")
     *
     * @return Response
     */
    public function getUserWalletBalanceAction()
    {
        $wallets = $this->getUser()
            ->getWallets();

        $serializedUserWallets = $this->get('jms_serializer')->serialize($wallets, 'json', SerializationContext::create()->setGroups(['api']));
        $userWallets = $this->get('jms_serializer')->deserialize($serializedUserWallets, 'array<'.Wallet::class.'>', 'json');

        return $this->handleView(
            $this->view([
                'wallets' => $userWallets,
            ], Response::HTTP_OK)
        );
    }

    /**
     * Withdraws to user.
     *
     * ### Request URL example ###
    Post /api/v1/users/withdraw
    body: {"amount":"1.2","bankSlug":"btc","foreignAccount":"123abc","authCode":"aaa999"}
     * ### Success response example ###
     *     {
     *      "isSuccess": true
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA | VALIDATION_ERROR | NOT_ENOUGH_MONEY | OPTIMISTIC_LOCK_FAILED",
     *     }
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND",
     *     }
     *     {
     *       "status": 500,
     *       "error": "UNKNOWN_ERROR",
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Withdraws to user",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned something is wrong",
     *     Response::HTTP_NOT_FOUND = "Returned when data is not found",
     *     Response::HTTP_INTERNAL_SERVER_ERROR = "Returned something is wrong"
     *   },
     *   section = "Wallet",
     *   authentication = true
     * )
     *
     * @Annotations\POST("/users/withdraw")
     *
     * @Annotations\RequestParam(name="amount", requirements="(\d*[.])?\d+")
     * @Annotations\RequestParam(name="bankSlug", requirements="\S+", description="Bank slug. Value egopay, btc, okpay, eth, bnk, paypal and etc.")
     * @Annotations\RequestParam(name="foreignAccount", requirements="[\d\S]+")
     * @Annotations\RequestParam(name="authCode", requirements="[\d\S]+")
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws NotFoundException
     * @throws UnknownErrorException
     * @throws NotValidDataException
     * @throws NotEnoughMoneyException
     * @throws OptimisticLockException
     */
    public function withdrawAction(Request $request)
    {
        /*** @var Bank $bank */
        $bank = $this->get('rest.repository.bank')->findOneBy(['slug' => $request->get('bankSlug')]);

        if (!$bank instanceof Bank) {
            throw new NotFoundException();
        }

        $paymentFactory = $this->get('exmarkets_transfer.payment.factory');

        // will throw not found exception if bank does not support withdrawal
        $model = $paymentFactory->withdrawalModel($bank);

        // prepare a response builder function
        $currency = $this->get('rest.repository.currency')->findOneBy(['code' => $bank->getSlug()]);
        $model->setCurrency($currency);

        $form = $this->get('form.factory')->create($paymentFactory->withdrawalForm($bank), $model);
        $form->submit($request);

        if ($form->isValid()) {
            $user = $this->getUser();
            /** @var Wallet $wallet */
            $wallet = $this->get('rest.repository.wallet')->findOneForUserAndCurrency($user, $currency);
            $model->setWalletId($wallet->getId());
            $model->setFeeAmount($model->getFeeApplied());

            // checking if withdrawal address is someones deposit address
            if ($this->get('rest.repository.deposit_address')->findOneBy(['address' => $model->getForeignAccount()])) {
                throw new UnknownErrorException();
            }

            /** @var CoinApiInterface $addressService */
            $addressService = $this->get(sprintf('exm_payment_core.gateway.coin.%s.api', strtolower($currency->getCode())));
            $isValidAddress = $addressService->isAddressValid($model->getForeignAccount());

            if (!$isValidAddress) {
                throw new NotValidDataException();
            }
            // simulates same logic as before, we have to persist with approving status
            $model->approving();

            // attempt to persist withdrawal with wallet balance lock
            $persister = $this->get('rest.service.withdrawal_persister');

            if ($error = $persister->requestWithdrawal($model)) {
                throw new NotEnoughMoneyException();
            }

            try {
                $this->incHotpCounter($user);
            } catch (OptimisticLockException $e) {
                throw new OptimisticLockException();
            }

            $this->get('rest.service.notifications')->notifyAboutWithdraw($model, $bank);
            $this->get('rest.service.activity_logger')->logUserWithdraw($user, $request);

            return $this->handleView(
                $this->view([
                    'isSuccess' => true,
                ], Response::HTTP_OK)
            );
        }

        throw new NotValidDataException();
    }

    /**
     * @param User $user
     *
     * @return bool
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     *                                               TODO: remove to the service
     */
    protected function incHotpCounter(User $user)
    {
        if ($user->hasHOTP()) {
            $user->setHotpAuthCounter($user->getHotpAuthCounter() + 1);
            $user->setHotpSentTimes(0);
            $this->get('em')->flush($user);

            return true;
        }

        return false;
    }

    /**
     * Changes user password.
     *
     * ### Request URL example ###
    PATCH /api/v1/users/passwords
    body: {"newPassword":"123abc"}
     * ### Success response example ###
     *     {
     *      "isSuccess": true
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA",
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Changes user password",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned something is wrong"
     *   },
     *   section = "User",
     *   authentication = true
     * )
     *
     * @Annotations\Patch("/users/passwords")
     *
     * @Annotations\RequestParam(name="newPassword", requirements="\S+")
     *
     * @param ParamFetcher $paramFetcher
     * @param Request      $request
     *
     * @return Response
     */
    public function changeUserPasswordAction(ParamFetcher $paramFetcher, Request $request)
    {
        $user = $this->getUser();

        $user->setPlainPassword($paramFetcher->get('newPassword'));
        $user->removeRole(User::FORCE_CHANGE_PASSWORD);
        $this->get('core.user_registration_service')
            ->encryptPassword($user);

        $user = $this->get('rest.service.user')
            ->patch($user);

        $this->get('rest.service.activity_logger')->logUserChangePassword($user, $request);

        return $this->handleView(
            $this->view([
                'isSuccess' => boolval($user),
            ], Response::HTTP_OK)
        );
    }

    /**
     * Gets user deposit history.
     *
     * ### Request URL example ###
     * GET/api/v1/deposits/histories?pageNum=1&limit=10&days=100&status=internal&currencyCode=usd
     * ### Success response example ###
     *     {
     *      "deposits": [{
     *          "id": 1,
     *          "wallet": {
     *          },
     *          "bank": {
     *          },
     *         "amount": 1.2,
     *         "fee_amount": 0,
     *         "status": "internal",
     *         "created_at": "2018-01-31T16:48:15+00:00",
     *         "updated_at": "2018-01-31T16:48:15+00:00",
     *         "logs": [{}],
     *         "wallet_operations": {}
     *      }],
     *      "total_pages": 2,
     *      "current_page": 1
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA",
     *     }
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND",
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets user deposit history",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned something is wrong",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "History",
     *   authentication = true
     * )
     *
     * @Annotations\QueryParam(name="pageNum", requirements="\d+", default="1", description="The number of page")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default="10", description="The limit of items")
     * @Annotations\QueryParam(name="days", requirements="\d+", nullable=true, description="Count of days before today")
     * @Annotations\QueryParam(name="status", requirements="\S+", nullable=true, description="Status name (new, internal, finished, canceled, approving and etc.)")
     * @Annotations\QueryParam(name="currencyCode", requirements="\S+", nullable=true, description="Currency code (usd, eur, eth and etc.)")
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return Response
     *
     * @throws NotFoundException
     */
    public function getDepositsHistoriesAction(ParamFetcherInterface $paramFetcher)
    {
        $days = $paramFetcher->get('days');
        $status = $paramFetcher->get('status');
        $currencyCode = $paramFetcher->get('currencyCode');
        $page = $paramFetcher->get('pageNum');
        $limit = $paramFetcher->get('limit');

        if ($currencyCode !== null) {
            $currency = $this->get('rest.service.currency')->getOneBy(['code' => $currencyCode]);

            if (!$currency instanceof Currency) {
                throw new NotFoundException();
            }
        } else {
            $currency = $this->get('rest.service.currency')->all(null);

            if (empty($currency)) {
                throw new NotFoundException();
            }
        }

        $qb = $this->get('rest.repository.deposit')->getUserDepositsQueryBuilder($this->getUser(), $days, $status, $currency);

        $target = new Target($qb);

        $request = new Request([
            'page' => $page,
            'limit' => $limit,
        ]);

        $data = $this->get('paginator')->paginate($request, $target);

        return $this->handleView(
            $this->view([
                'deposits' => $data->getItems(),
                'total_pages' => $data->getTotalPageCount(),
                'current_page' => $data->getCurrentPageNumber(),
            ], Response::HTTP_OK)
        );
    }

    /**
     * Gets user withdraw history.
     *
     * ### Request URL example ###
     * GET/api/v1/withdraws/histories?pageNum=1&limit=10&days=100&status=internal&currencyCode=usd
     * ### Success response example ###
     *     {
     *      "withdraws": [{
     *          "id": 1,
     *          "wallet": {
     *          },
     *          "bank": {
     *          },
     *         "amount": 1.2,
     *         "fee_amount": 0,
     *         "status": "internal",
     *         "created_at": "2018-01-31T16:48:15+00:00",
     *         "updated_at": "2018-01-31T16:48:15+00:00",
     *         "logs": [{}],
     *         "wallet_operations": {}
     *      }],
     *      "total_pages": 2,
     *      "current_page": 1
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA",
     *     }
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND",
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets user withdraw history",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned something is wrong",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "History",
     *   authentication = true
     * )
     *
     * @Annotations\QueryParam(name="pageNum", requirements="\d+", default="1", description="The number of page")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default="10", description="The limit of items")
     * @Annotations\QueryParam(name="days", requirements="\d+", nullable=true, description="Count of days before today")
     * @Annotations\QueryParam(name="status", requirements="\S+", nullable=true, description="Status name (new, internal, finished, canceled, approving and etc.)")
     * @Annotations\QueryParam(name="currencyCode", requirements="\S+", nullable=true, description="Currency code (usd, eur, eth and etc.)")
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return Response
     *
     * @throws NotFoundException
     */
    public function getWithdrawsHistoriesAction(ParamFetcherInterface $paramFetcher)
    {
        $days = $paramFetcher->get('days');
        $status = $paramFetcher->get('status');
        $currencyCode = $paramFetcher->get('currencyCode');
        $page = $paramFetcher->get('pageNum');
        $limit = $paramFetcher->get('limit');

        if ($currencyCode !== null) {
            $currency = $this->get('rest.service.currency')->getOneBy(['code' => $currencyCode]);

            if (!$currency instanceof Currency) {
                throw new NotFoundException();
            }
        } else {
            $currency = $this->get('rest.service.currency')->all(null);

            if (empty($currency)) {
                throw new NotFoundException();
            }
        }

        $qb = $this->get('rest.repository.withdraw')->getUserWithdrawsQueryBuilder($this->getUser(), $days, $status, $currency);

        $target = new Target($qb);

        $request = new Request([
            'page' => $page,
            'limit' => $limit,
        ]);

        $data = $this->get('paginator')->paginate($request, $target);

        return $this->handleView(
            $this->view([
                'withdraws' => $data->getItems(),
                'total_pages' => $data->getTotalPageCount(),
                'current_page' => $data->getCurrentPageNumber(),
            ], Response::HTTP_OK)
        );
    }

    /**
     * Generates new user address for the cryptocurrency.
     *
     * ### Request URL example ###
    POST /api/v1/users/cryptoaddresses
    body: {"currencyId":"1"}
     * ### Success response example ###
     *     {
     *      "newAddress": "address"
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA"
     *     }
     *     {
     *       "status": 400,
     *       "error": "TOO_OFTEN_ADDRESS_REQUEST"
     *     }
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Generates new user address for the cryptocurrency",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned something is wrong"
     *   },
     *   section = "Crypto Address",
     *   authentication = true
     * )
     * @Annotations\Post("/users/cryptoaddresses")
     *
     * @Annotations\RequestParam(name="currencyId", requirements="\d+")
     *
     * @param ParamFetcher $paramFetcher
     *
     * @return Response
     *
     * @throws NotFoundException
     * @throws TooOftenAddressRequestException
     */
    public function generateUserCryptoAddressAction(ParamFetcher $paramFetcher)
    {
        $currency = $this->get('rest.service.currency')->get($paramFetcher->get('currencyId'));

        if (!$currency instanceof Currency) {
            throw new NotFoundException();
        }

        try {
            $serviceCurrencyCode = $currency->isEth() ? Currency::ETH_CURRENCY_SERVICE_CODE : $currency->getCode(); //all tokens use eth API
            $addressService = $this->get(sprintf('rest.service.coin.%s.address', strtolower($serviceCurrencyCode)));
            $newAddress = $addressService->requestNewAddress($this->getUser(), $currency);

            return $this->handleView(
                $this->view([
                    'newAddress' => $newAddress,
                ], Response::HTTP_OK)
            );
        } catch (NewAddressLimitReachedException $e) {
            throw new TooOftenAddressRequestException();
        }
    }

    /**
     * Gets user addresses for the cryptocurrency.
     *
     * ### Request URL example ###
     * GET/api/v1/users/cryptoaddresses/2
     * ### Success response example ###
     *     {
     *      "address": "test",
     *      "addresses": [{
     *         "id": 1,
     *          "user": {
     *              "id": 1,
     *              "username": "Q999996",
     *              "email": "admin5@exmarkets.com",
     *              "salt": "ao3sald11s00kko0o0s8w8g4g4ssggk",
     *              "password": "$2y$10$ao3sald11s00kko0o0s8wufAb6vkqpGzX2rTfFUpvMypv6fJGepC6",
     *              "roles": [
     *                  "ROLE_USER"
     *              ],
     *              "auth_key": "OJEHTFBWJXEYLGHZAMSTLBTYZ3I7Y4VX",
     *              "hotp_auth_counter": 0,
     *              "wallets": [{
     *                  "id": 1,
     *                  "currency": {
     *                      "id": 1,
     *                      "code": "USD",
     *                      "sign": "$",
     *                      "format": 2,
     *                      "crypto": false,
     *                      "eth": false,
     *                      "is_erc_token": false,
     *                      "contract_address": "",
     *                      "contract_abi": ""
     *                  },
     *                  "balance": 10044.24498674,
     *                  "reserved": 2765.39,
     *                  "total": 7275.85498674,
     *                  "fee_percent": 0,
     *                  "created_at": "2017-09-27T12:45:38+00:00",
     *                  "updated_at": "2018-01-31T16:48:15+00:00",
     *                  "wallet_operations": [{
     *                      "id": 1,
     *                      "deposit": {
     *                          "id": 25,
     *                          "bank": {
     *                              "id": 11,
     *                              "name": "Wire Transfer",
     *                              "slug": "international-wire-transfer",
     *                              "fiat": true,
     *                              "payment_method": "wire",
     *                              "deposit_available": true,
     *                              "withdrawal_available": true
     *                          },
     *                          "amount": 1,
     *                          "fee_amount": 0,
     *                          "status": "internal",
     *                          "created_at": "2017-12-13T15:58:23+00:00",
     *                          "updated_at": "2017-12-17T15:58:23+00:00",
     *                          "logs": [],
     *                          "wallet_operations": []
     *                      },
     *                      "balance": 0,
     *                      "total_reserved": 0,
     *                      "expense": 0,
     *                      "debit": 0,
     *                      "credit": 1,
     *                      "reserve": 0,
     *                      "type": "1",
     *                      "created_at": "2017-12-17T15:58:23+00:00"
     *                  }]
     *              }]
     *          }],
     *          "currency": {
     *              "id": 2,
     *              "code": "BTC",
     *              "sign": "฿",
     *              "format": 8,
     *              "crypto": true,
     *              "eth": false,
     *              "is_erc_token": false,
     *              "contract_address": "",
     *              "contract_abi": ""
     *          },
     *          "currencies": [{
     *              "id": 2,
     *              "code": "BTC",
     *              "sign": "฿",
     *              "format": 8,
     *              "crypto": true,
     *              "eth": false,
     *              "is_erc_token": false,
     *              "contract_address": "",
     *              "contract_abi": ""
     *          }]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA",
     *     }
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND",
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Get user addresses for the cryptocurrency",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned something is wrong"
     *   },
     *   section = "Crypto Address",
     *   authentication = true
     * )
     * @Annotations\Get("/users/cryptoaddresses/{currencyId}", requirements = { "currencyId" = "\d+" })
     *
     * @param int $currencyId
     *
     * @return Response
     *
     * @throws NotFoundException
     */
    public function getUserCryptoAddressAction($currencyId)
    {
        $currency = $this->get('rest.service.currency')->get($currencyId);

        if (!$currency instanceof Currency) {
            throw new NotFoundException();
        }

        $addresses = $this->get('rest.repository.deposit_address')->findUserAddresses($this->getUser(), $currency);
        $currencies = $this->get('rest.repository.currency')->getVirtualCurrencies();

        $serviceCurrencyCode = $currency->isEth() ? Currency::ETH_CURRENCY_SERVICE_CODE : $currency->getCode(); //all tokens use eth API
        $addressService = $this->get(sprintf('rest.service.coin.%s.address', strtolower($serviceCurrencyCode)));
        $address = $addressService->getAddress($this->getUser(), $currency);

        return $this->handleView(
            $this->view([
                'address' => $address,
                'addresses' => $addresses,
                'currency' => $currency,
                'currencies' => $currencies,
            ], Response::HTTP_OK)
        );
    }

    /**
     * Sends pin to the phone.
     *
     * ### Request URL example ###
    PATCH /api/v1/users/security/pins/send
    body: {"phone":"+375228901912"}
     * ### Success response example ###
     *     {
     *      "isSuccess": true
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Sends pin to the phone",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned something is wrong"
     *   },
     *   section = "User",
     *   authentication = true
     * )
     * @Annotations\Patch("/users/security/pins/send")
     *
     * @Annotations\RequestParam(name="phone", requirements="\S+")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function sendPinPhoneAction(Request $request)
    {
        $user = $this->getUser();

        $verification = $this->get('rest.repository.phone_verification')->findNotConfirmedByUser($user);

        $phoneVerification = new PhoneVerification();
        $phoneVerification->setUser($user);

        if ($verification instanceof PhoneVerification) {
            $phoneVerification->setPhone($verification->getPhone());
        }

        $phoneVerification = $this->get('rest.service.user')
            ->processPhoneVerificationForm($request, $phoneVerification);

        return $this->handleView(
            $this->view([
                'isSuccess' => boolval($phoneVerification),
            ], Response::HTTP_OK)
        );
    }

    /**
     * Verifies phone pin code.
     *
     * ### Request URL example ###
    PATCH /api/v1/users/security/phones/verify
    body: {"pin":"123456"}
     * ### Success response example ###
     *     {
     *      "isSuccess": true
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Verifies phone pin code",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned something is wrong"
     *   },
     *   section = "User",
     *   authentication = true
     * )
     * @Annotations\Patch("/users/security/phones/verify")
     *
     * @Annotations\RequestParam(name="pin", requirements="\d+")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function verifyPhonePinCodeAction(Request $request)
    {
        $phoneVerification = $this->get('rest.service.user')
            ->processPhonePinVerificationForm($request, $this->getUser());

        return $this->handleView(
            $this->view([
                'isSuccess' => boolval($phoneVerification),
            ], Response::HTTP_OK)
        );
    }

    /**
     * Gets user activities.
     *
     * ### Request URL example ###
     * GET /api/v1/users/activities?from=2018/02/22&to=2018/02/23&limit=10&pageNum=1&action=btc_user.security.profile_edit_completed
     * ### Success response example ###
     *     {
     *      "activities": [{
     *          "id": 136,
     *          "action": "btc_user.security.profile_edit_completed",
     *          "ip_address": "192.168.50.1",
     *          "additional_info": [],
     *          "created_at": "2018-01-29T00:26:11+00:00"
     *      }],
     *      "total_pages": 6,
     *      "current_page": 1
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets user activities",
     *   output = {
     *     "class" = "Btc\CoreBundle\Entity\Activity",
     *     "groups" = "api"
     *   },
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned something is wrong"
     *   },
     *   section = "User",
     *   authentication = true
     * )
     *
     * @Annotations\Get("/users/activities")
     *
     * @Annotations\QueryParam(name="limit", requirements="\d+", default="10")
     * @Annotations\QueryParam(name="pageNum", requirements="\d+", default="1")
     * @Annotations\QueryParam(name="from", requirements="^\d{4}/\d{2}/\d{2}$", default="")
     * @Annotations\QueryParam(name="to", requirements="^\d{4}/\d{2}/\d{2}$", default="")
     * @Annotations\QueryParam(name="action", requirements="\S+")
     *
     * @param ParamFetcher $paramFetcher
     *
     * @return Response
     */
    public function getUserActivitiesAction(ParamFetcher $paramFetcher)
    {
        $limit = $paramFetcher->get('limit');
        $action = $paramFetcher->get('action');
        $page = $paramFetcher->get('pageNum');
        $dateFrom = $paramFetcher->get('from');
        $dateTo = $paramFetcher->get('to');

        $qb = $this->get('rest.repository.activity')->getActivitiesQueryBuilder($this->getUser());

        $filters = [
            new ActivitiesDateRangeFilter(),
            new ActivitiesActionFilter(),
            new PageLimitFilter(),
        ];

        $target = new Target($qb, null, $filters);

        $request = new Request([
            'limit' => $limit,
            'page' => $page,
            'action' => $action,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);

        $data = $this->get('paginator')->paginate($request, $target);

        $serializer = SerializerBuilder::create()->build();
        $serializedActivities = $serializer->serialize($data->getItems(), 'json', SerializationContext::create()->setGroups(['api']));
        $activities = $serializer->deserialize($serializedActivities, 'array<'.Activity::class.'>', 'json');

        return $this->handleView(
            $this->view([
                'activities' => $activities,
                'total_pages' => $data->getTotalPageCount(),
                'current_page' => $data->getCurrentPageNumber(),
            ], Response::HTTP_OK)
        );
    }

    /**
     * Changes user preferences.
     *
     * ### Request URL example ###
    PATCH /api/v1/users/preferences
    body: {"rows":{"0":{"value":true},"1":{"value":false}}}
     * ### Success response example ###
     *     {
     *      "isSuccess": true
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "VALIDATION_ERROR"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Changes user preferences",
     *   input = "Btc\FrontendApiBundle\Form\PreferencesType",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned something is wrong"
     *   },
     *   section = "User",
     *   authentication = true
     * )
     *
     * @Annotations\Patch("/users/preferences")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function changeUserPreferencesAction(Request $request)
    {
        $user = $this->getUser();

        $user = $this->get('rest.service.user')
            ->processChangeUserPreferencesForm($request, $user);

        return $this->handleView(
            $this->view([
                'isSuccess' => boolval($user),
            ], Response::HTTP_OK)
        );
    }

    /**
     * Turns on google authorization.
     *
     * ### Request URL example ###
    PATCH /api/v1/users/security/auth/google/statuses/on
    body: {"auth_code":"123abc"}
     * ### Success response example ###
     *     {
     *      "isSuccess": true
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "VALIDATION_ERROR"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Turns on google authorization",
     *   input = "Btc\FrontendApiBundle\Form\TwoFactorAdditionType",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned something is wrong"
     *   },
     *   section = "Google Auth",
     *   authentication = true
     * )
     * @Annotations\Patch("/users/security/auth/google/statuses/on")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function turnOnGoogleAuthorizationAppAction(Request $request)
    {
        $user = $this->get('rest.service.user')
            ->processGoogleAuthForm($request, $this->getUser(), RestSecurity::GOOGLE_AUTH_TURN_ON);

        return $this->handleView(
            $this->view([
                'isSuccess' => boolval($user),
            ], Response::HTTP_OK)
        );
    }

    /**
     * Gets google authorization app.
     *
     * ### Request URL example ###
     * GET /api/v1/users/security/auth/google
     * ### Success response example ###
     *     {
     *      "qr_code": "https://chart.googleapis.com/chart?chs=123&chld=M|0&cht=qr&chl=abc",
     *      "auth_key": "abc123"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets google authorization app",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful"
     *   },
     *   section = "Google Auth",
     *   authentication = true
     * )
     * @Annotations\Get("/users/security/auth/google")
     *
     * @return Response
     */
    public function getGoogleAuthorizationAppAction()
    {
        $user = $this->get('rest.service.user')->setNewAuthKey($this->getUser());

        return $this->handleView(
            $this->view([
                'qr_code' => $this->get('rest.service.qr_code')->getUrl($user->getUsername(), $user->getAuthKey()),
                'auth_key' => $user->getAuthKey(),
            ], Response::HTTP_OK)
        );
    }

    /**
     * Turns off google authorization.
     *
     * ### Request URL example ###
    PATCH /api/v1/users/security/auth/google/statuses/off
    body: {"auth_code":"123abc"}
     * ### Success response example ###
     *     {
     *      "isSuccess": true
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "VALIDATION_ERROR"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Turns off google authorization",
     *   input = "Btc\FrontendApiBundle\Form\TwoFactorRemovalType",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned something is wrong"
     *   },
     *   section = "Google Auth",
     *   authentication = true
     * )
     * @Annotations\Patch("/users/security/auth/google/statuses/off")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function turnOffGoogleAuthorizationAppAction(Request $request)
    {
        $user = $this->get('rest.service.user')
            ->processGoogleAuthForm($request, $this->getUser(), RestSecurity::GOOGLE_AUTH_TURN_OFF);

        return $this->handleView(
            $this->view([
                'isSuccess' => boolval($user),
            ], Response::HTTP_OK)
        );
    }

    /**
     * Gets user stats.
     *
     * ### Request URL example ###
     * GET /api/v1/users/stats
     * ### Success response example ###
     *     {
     *      "user_stats": {
     *          "user": {},
     *          "fee_set": {}
     *      }
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets user stats",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful"
     *   },
     *   section = "User",
     *   authentication = true
     * )
     *
     * @return Response
     */
    public function getUsersStatsAction()
    {
        $userFeeSetService = $this->get('rest.service.user_fee_set');
        $user = $this->getUser();

        return $this->handleView(
            $this->view([
                'user_stats' => [
                    'user' => $user,
                    'fee_set' => $userFeeSetService->getOneBy(['user' => $user]),
                ],
            ], Response::HTTP_OK)
        );
    }

    /**
     * @ApiDoc(
     *   resource = true,
     *   description = "Prelogins user by email",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when something is wrong"
     *   },
     *   section = "Test"
     * )
     *
     * @Annotations\Post("/test/users/prelogin")
     *
     * @Annotations\RequestParam(name="email", requirements="\S+")
     *
     * @param ParamFetcher $fetcher
     *
     * @return Response
     *
     * @throws NotValidDataException
     * @throws UserBlockedException
     * @throws InvalidCredentialsException
     */
    public function postUserPreLoginByEmailTestAction(ParamFetcher $fetcher)
    {
        $email = $fetcher->get('email');

        $isNotEmail = $this->get('validator.builder')->getValidator()
            ->validate($email, new Email())
            ->count();

        if ($isNotEmail) {
            throw new NotValidDataException();
        }

        $userService = $this->get('rest.service.user');

        $user = $userService->getOneBy(['email' => $email]);

        if ($user instanceof User) {
            if ($user->hasRole(User::BLOCKED)) {
                throw new UserBlockedException();
            }

            $key = sprintf(RestSecurity::CNT_REQUEST_GENERATE_PIN_KEY, $user->getId());

            $redisService = $this->get('rest.redis');

            if ($redisService->get($key) >= RestSecurity::ATTEMPT_GENERATE_PIN) {
                $userService->blockedUser($user);

                throw new UserBlockedException();
            }

            $pinService = $this->get('rest.service.pin');

            $sourcePin = $pinService->generate(7);
            $encodedPin = $pinService->encodePin($sourcePin, $user->getSalt());

            $user->setPin($encodedPin);

            $userService->patch($user);

            $user->setPin($sourcePin);

            $redisService->incr($key);


        } else {
            throw new InvalidCredentialsException();
        }

        return $this->handleView(
            $this->view([
                'status' => boolval($user),
                'pin' => $sourcePin,
            ], Response::HTTP_OK)
        );
    }
}
