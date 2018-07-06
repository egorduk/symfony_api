<?php

namespace spec\Btc\FrontendApiBundle\Controller;

use Btc\CoreBundle\Entity\Article;
use Btc\FrontendApiBundle\Controller\NewsController;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\FrontendApiBundle\Service\NewsService;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class NewsControllerSpec extends ObjectBehavior
{
    public function let(
        ContainerInterface $container,
        ViewHandler $viewHandler,
        NewsService $newsService
    ) {
        $this->setContainer($container);

        $container->get('rest.service.news')->willReturn($newsService);
        $container->get('fos_rest.view_handler')->willReturn($viewHandler);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(NewsController::class);
        $this->shouldHaveType(FOSRestController::class);
    }

    public function it_should_respond_to_get_news_action(
        Response $response,
        ViewHandler $viewHandler,
        NewsService $newsService,
        ParamFetcherInterface $paramFetcher,
        Article $article
    ) {
        $newsService->findAllPublished(Argument::any(), Argument::any())->willReturn([$article]);

        $viewHandler->handle(Argument::type(View::class))->willReturn($response);

        $response = $this->getNewsAction($paramFetcher);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_get_news_by_slug_action(
        Response $response,
        ViewHandler $viewHandler,
        NewsService $newsService,
        Article $article
    ) {
        $newsService->findOneBySlug(Argument::any())->willReturn($article);

        $viewHandler->handle(Argument::type(View::class))->willReturn($response);

        $response = $this->getNewsBySlugAction('slug');
        $response->shouldHaveType(Response::class);
    }

    public function it_throws_an_exception_if_items_not_found(NewsService $newsService, ParamFetcherInterface $paramFetcher)
    {
        $newsService->findAllPublished(Argument::any(), Argument::any())->willReturn([]);

        $this
            ->shouldThrow(NotFoundException::class)
            ->duringGetNewsAction($paramFetcher);
    }

    public function it_throws_an_exception_if_item_not_found(NewsService $newsService)
    {
        $newsService->findOneBySlug(Argument::any())->willReturn([]);

        $this
            ->shouldThrow(NotFoundException::class)
            ->duringGetNewsBySlugAction('slug');
    }
}
