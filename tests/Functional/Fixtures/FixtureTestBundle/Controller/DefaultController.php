<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Controller;

use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\MyObject;
use Symfony\Component\HttpFoundation\Response;

class DefaultController
{
    public function action1Action(string $parameter = 'default'): Response
    {
        return new Response($parameter);
    }

    public function action1bAction(string $parameter = 'default'): Response
    {
        return new Response($parameter);
    }

    public function action2(string $parameter = 'default'): Response
    {
        return new Response($parameter);
    }

    public function action3(string $parameter = 'default'): Response
    {
        return new Response($parameter);
    }

    public function action4(string $parameter = 'default'): Response
    {
        return new Response($parameter);
    }

    public function action5(string $parameter = 'default'): Response
    {
        return new Response($parameter);
    }

    public function action($parameter = 'default'): Response
    {
        return new Response((string) $parameter);
    }

    public function actionWithMultipleParameters($parameter1, $parameter2): Response
    {
        return new Response($parameter1 . ' ' . $parameter2);
    }

    public function actionWithReturn(): MyObject
    {
        return (new MyObject())->setField1('field from controller');
    }
}
