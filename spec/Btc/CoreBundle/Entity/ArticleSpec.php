<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\Article;
use Btc\CoreBundle\Entity\RestEntityInterface;
use PhpSpec\ObjectBehavior;

class ArticleSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Article::class);
        $this->shouldImplement(RestEntityInterface::class);
    }
}
