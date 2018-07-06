<?php

namespace spec\Btc\FrontendApiBundle\Controller;

use Btc\CoreBundle\Entity\Page;
use Btc\FrontendApiBundle\Controller\PageController;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\FrontendApiBundle\Repository\PageRepository;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use JMS\Serializer\Serializer;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class PageControllerSpec extends ObjectBehavior
{
    public function let(
        ContainerInterface $container,
        ViewHandler $viewHandler,
        PageRepository $pageRepository,
        Serializer $serializer
    ) {
        $this->setContainer($container);

        $container->get('rest.repository.page')->willReturn($pageRepository);
        $container->get('jms_serializer')->willReturn($serializer);
        $container->get('fos_rest.view_handler')->willReturn($viewHandler);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PageController::class);
        $this->shouldHaveType(FOSRestController::class);
    }

    public function it_should_respond_to_get_page_action(
        ParamFetcher $paramFetcher,
        Response $response,
        PageRepository $pageRepository,
        ViewHandler $viewHandler,
        Page $page
    ) {
        $paramFetcher->get('path')->willReturn('path');
        $paramFetcher->get('locale')->willReturn('locale');

        $pageRepository->findPage(Argument::any(), Argument::any())->willReturn($page)->shouldBeCalled();

        $view = new View(['page' => []], Response::HTTP_OK);

        $viewHandler->handle($view)->willReturn($response)->shouldBeCalled();

        $response = $this->getPageAction($paramFetcher);
        $response->shouldHaveType(Response::class);
    }

    public function it_throws_an_exception_if_data_not_found(PageRepository $pageRepository, ParamFetcher $paramFetcher)
    {
        $pageRepository->findPage(Argument::any(), Argument::any())->willReturn(null);

        $this
            ->shouldThrow(NotFoundException::class)
            ->duringGetPageAction($paramFetcher);
    }
}
