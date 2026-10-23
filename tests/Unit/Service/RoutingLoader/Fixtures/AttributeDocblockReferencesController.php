<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Attribute\Body;
use Paysera\Bundle\ApiBundle\Attribute\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

use function strlen;

/**
 * Migrated from the annotations to {@see Body} and \Paysera\Bundle\ApiBundle\Attribute\Query.
 */
class AttributeDocblockReferencesController
{
    /**
     * {@inheritdoc}
     *
     * @see \Paysera\Bundle\ApiBundle\Attribute\Body
     * @param string $data the body, see Body::class
     * @OA\RequestBody(description="a tag of another library")
     */
    #[RequiredPermissions(permissions: ['ROLE_ADMIN'])]
    #[Route('/attribute-docblock-references', methods: ['POST'])]
    public function show(string $data = ''): int
    {
        $text = <<<'TEXT'
@Body(parameterName="data") was the annotation
TEXT;
        $length = static function () use ($text, $data): int {
            return strlen($text . $data);
        };
        return $length();
    }
}
