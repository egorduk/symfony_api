<?php

namespace Btc\FrontendApiBundle\Controller;

use Btc\CoreBundle\Entity\Currency;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\PaginationBundle\Target;
use Btc\Component\Market\Model\Voucher;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Voucher as VoucherEntity;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Request\ParamFetcherInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class VoucherController extends FOSRestController
{
    /**
     * Creates voucher.
     *
     * ### Request URL example ###
     POST /api/v1/vouchers
     body: {"amount":"100","currency":"1"}
     *
     * ### Success response example ###
     *      {
     *          {
     *              "id": 14,
     *              "code": "VX-2XPA-BVFG-K5UW",
     *              "amount": 1,
     *              "currency": {
     *                  "id": 1,
     *                  "code": "USD",
     *                  "sign": "$",
     *                  "format": 2,
     *                  "crypto": false,
     *                  "eth": false,
     *                  "is_erc_token": false,
     *                  "contract_address": "",
     *                  "contract_abi": ""
     *              }
     *              "status": 1,
     *              "created_by_user": {
     *                  "id": 1,
     *                  "username": "Q999990",
     *                  "email": "admin51@exmarkets.com",
     *              }
     *              "created_at": "2018-03-09T16:54:48+00:00"
     *          ]
     *      }
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
     *   description = "Creates voucher",
     *   input = "Btc\FrontendApiBundle\Form\CreateVoucherType",
     *   output = "Btc\CoreBundle\Entity\Voucher",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned something is wrong",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Voucher",
     *   authentication = true
     * )
     *
     * @Annotations\Post("/vouchers")
     *
     * @Annotations\RequestParam(name="amount", requirements="[0-9\.]+", default="0", description="Amount")
     * @Annotations\RequestParam(name="currency", requirements="\d+", description="Currency id")
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws NotFoundException
     */
    public function createVoucherAction(Request $request)
    {
        $voucherService = $this->get('rest.service.voucher');

        $voucher = $voucherService->processCreateForm($request);
        $voucher = $this->prepareCreatingVoucher($this->getUser(), $voucher);
        $voucher = $voucherService->create($voucher, $request);

        return $this->handleView(
            $this->view([
                'voucher' => $voucher,
            ], Response::HTTP_OK)
        );
    }

    /**
     * Gets all vouchers.
     *
     * ### Request URL example ###
     * GET /api/v1/vouchers?limit=10&pageNum=1
     * ### Success response example ###
     *      {
     *          "vouchers": [{
     *              "id": 14,
     *              "code": "VX-2XPA-BVFG-K5UW",
     *              "amount": 1,
     *              "currency": {
     *                  "id": 1,
     *                  "code": "USD",
     *                  "sign": "$",
     *                  "format": 2,
     *                  "crypto": false,
     *                  "eth": false,
     *                  "is_erc_token": false,
     *                  "contract_address": "",
     *                  "contract_abi": ""
     *              }
     *              "status": 1,
     *              "created_by_user": {
     *                  "id": 1,
     *                  "username": "Q999990",
     *                  "email": "admin51@exmarkets.com",
     *              }
     *              "created_at": "2018-03-09T16:54:48+00:00"
     *          }],
     *          "total_pages": 2,
     *          "current_page": 1
     *      }
     * ### Error response example ###
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets all vouchers",
     *   output = {
     *     "class" = "Btc\CoreBundle\Entity\Voucher",
     *     "groups" = {"api", "api_vouchers"},
     *     "parsers" = {"Nelmio\ApiDocBundle\Parser\JmsMetadataParser"}
     *   },
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Voucher"
     * )
     *
     * @Annotations\QueryParam(name="pageNum", requirements="\d+", default="1", description="The number of page")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default="10", description="How many items to return.")
     *
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Response
     *
     * @throws NotFoundException when items do not exist
     */
    public function getVouchersAction(ParamFetcherInterface $paramFetcher)
    {
        $pageNum = $paramFetcher->get('pageNum');
        $limit = $paramFetcher->get('limit');

        $voucherRepository = $this->get('rest.repository.voucher');

        $qb = $voucherRepository->getCreatedVouchersQueryBuilder();
        $counter = $voucherRepository->getCreatedVouchersQueryBuilder();

        $request = new Request([
            'page' => $pageNum,
            'limit' => $limit,
        ]);

        $target = new Target($qb);
        $target->setCounterQueryBuilder($counter);

        $data = $this->get('paginator')
            ->paginate($request, $target, true);

        $serializer = SerializerBuilder::create()->build();
        $serializedActivities = $serializer->serialize($data->getItems(), 'json', SerializationContext::create()->setGroups(['api', 'api_vouchers']));
        $vouchers = $serializer->deserialize($serializedActivities, 'array<'.VoucherEntity::class.'>', 'json');

        return $this->handleView(
            $this->view([
                'vouchers' => $vouchers,
                'total_pages' => $data->getTotalPageCount(),
                'current_page' => $data->getCurrentPageNumber(),
            ], Response::HTTP_OK)
        );
    }

    /**
     * Gets user vouchers.
     *
     * ### Request URL example ###
     * GET /api/v1/users/vouchers?limit=10&pageNum=1
     * ### Success response example ###
     *      {
     *          "vouchers": [{
     *              "id": 14,
     *              "code": "VX-2XPA-BVFG-K5UW",
     *              "amount": 1,
     *              "currency": {
     *                  "id": 1,
     *                  "code": "USD",
     *                  "sign": "$",
     *                  "format": 2,
     *                  "crypto": false,
     *                  "eth": false,
     *                  "is_erc_token": false,
     *                  "contract_address": "",
     *                  "contract_abi": ""
     *              }
     *              "status": 1,
     *              "created_by_user": {
     *                  "id": 1,
     *                  "username": "Q999990",
     *                  "email": "admin51@exmarkets.com",
     *              }
     *              "created_at": "2018-03-09T16:54:48+00:00"
     *          }],
     *          "total_pages": 2,
     *          "current_page": 1
     *      }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets user vouchers",
     *   output = {
     *     "class" = "Btc\CoreBundle\Entity\Voucher",
     *     "groups" = {"api", "api_vouchers"},
     *     "parsers" = {"Nelmio\ApiDocBundle\Parser\JmsMetadataParser"}
     *   },
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful"
     *   },
     *   section = "Voucher"
     * )
     *
     * @Annotations\QueryParam(name="pageNum", requirements="\d+", default="1", description="The number of page")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default="10", description="How many items to return.")
     *
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Response
     *
     * @throws NotFoundException when items do not exist
     */
    public function getUsersVouchersAction(ParamFetcherInterface $paramFetcher)
    {
        $pageNum = $paramFetcher->get('pageNum');
        $limit = $paramFetcher->get('limit');

        $voucherRepository = $this->get('rest.repository.voucher');

        $user = $this->getUser();

        $qb = $voucherRepository->getCreatedVouchersByUserQueryBuilder($user);
        $counter = $voucherRepository->getCreatedVouchersByUserQueryBuilder($user);

        $request = new Request([
            'page' => $pageNum,
            'limit' => $limit,
        ]);

        $target = new Target($qb);
        $target->setCounterQueryBuilder($counter);

        $data = $this->get('paginator')
            ->paginate($request, $target, true);

        $serializer = SerializerBuilder::create()->build();
        $serializedActivities = $serializer->serialize($data->getItems(), 'json', SerializationContext::create()->setGroups(['api', 'api_vouchers']));
        $vouchers = $serializer->deserialize($serializedActivities, 'array<'.VoucherEntity::class.'>', 'json');

        return $this->handleView(
            $this->view([
                'vouchers' => $vouchers,
                'total_pages' => $data->getTotalPageCount(),
                'current_page' => $data->getCurrentPageNumber(),
            ], Response::HTTP_OK)
        );
    }

    /**
     * Redeems voucher.
     *
     * ### Request URL example ###
     PATCH /api/v1/vouchers/statuses/redeem
     body: {"code":"VX-2XPA-BVFG-K5UW"}
     * ### Success response example ###
     *      {
     *          {
     *              "id": 14,
     *              "code": "VX-2XPA-BVFG-K5UW",
     *              "amount": 1,
     *              "currency": {
     *                  "id": 1,
     *                  "code": "USD",
     *                  "sign": "$",
     *                  "format": 2,
     *                  "crypto": false,
     *                  "eth": false,
     *                  "is_erc_token": false,
     *                  "contract_address": "",
     *                  "contract_abi": ""
     *              }
     *              "status": 1,
     *              "created_by_user": {
     *                  "id": 1,
     *                  "username": "Q999990",
     *                  "email": "admin51@exmarkets.com",
     *              },
     *              "redeemed_by_user": {
     *                  "id": 2,
     *                  "username": "Q999992",
     *                  "email": "admin21@exmarkets.com",
     *              },
     *              "redeemed_at": "2018-03-09T16:54:48+00:00"
     *          ]
     *      }
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
     *   description = "Redeems voucher",
     *   input = "Btc\FrontendApiBundle\Form\RedeemVoucherType",
     *   output = "Btc\CoreBundle\Entity\Voucher",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned something is wrong",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Voucher",
     *   authentication = true
     * )
     *
     * @Annotations\Patch("/vouchers/statuses/redeem")
     *
     * @Annotations\RequestParam(name="code", requirements="\S+", description="Voucher code")
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws NotFoundException
     */
    public function redeemVoucherAction(Request $request)
    {
        $voucherService = $this->get('rest.service.voucher');

        $voucher = $voucherService->processRedeemForm($request);

        if (!$voucher) {
            throw new NotFoundException();
        }

        $user = $this->getUser();

        $voucher = $this->prepareRedeemingVoucher($user, $voucher);
        $voucher = $voucherService->redeem($voucher, $request);

        return $this->handleView(
            $this->view([
                'voucher' => $voucher,
            ], Response::HTTP_OK)
        );
    }

    private function prepareRedeemingVoucher(User $user, VoucherEntity $voucherObject)
    {
        $walletRepository = $this->get('rest.repository.wallet');

        $voucher = new Voucher();
        $voucher->setId($voucherObject->getId());
        $voucher->setCode($voucherObject->getCode());
        $voucher->setCurrency($voucherObject->getCurrency());
        $voucher->setAmount($voucherObject->getAmount());
        $voucher->setIssuer($voucherObject->getCreatedByUser());
        $voucher->setRedeemer($user);

        $wallet = $walletRepository->findOneForUserAndCurrency($user, $voucherObject->getCurrency());
        $voucher->setRedeemerWallet($wallet);

        $wallet = $walletRepository->findOneForUserAndCurrency($voucherObject->getCreatedByUser(), $voucherObject->getCurrency());
        $voucher->setIssuerWallet($wallet);

        return $voucher;
    }

    private function prepareCreatingVoucher(User $user, Voucher $voucher)
    {
        $voucher->setIssuer($user);

        if (!$voucher->getCurrency() instanceof Currency) {
            throw new NotFoundException();
        }

        $wallet = $this->get('rest.repository.wallet')
            ->findOneForUserAndCurrency($user, $voucher->getCurrency());
        $voucher->setIssuerWallet($wallet);

        return $voucher;
    }
}
