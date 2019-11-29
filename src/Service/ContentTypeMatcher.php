<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service;

/**
 * @internal
 */
class ContentTypeMatcher
{
    /**
     * @param string $fullContentType
     * @param array $supportedContentTypes Can be `something/something`, `something/*` or `*` to allow all
     * @return bool
     */
    public function isContentTypeSupported(string $fullContentType, array $supportedContentTypes): bool
    {
        $contentType = $this->removeDirectives($fullContentType);
        foreach ($supportedContentTypes as $availableContentType) {
            if ($contentType === $availableContentType) {
                return true;
            }

            if ($availableContentType === '*') {
                return true;
            }

            $availableParts = explode('/', $availableContentType, 2);
            $providedParts = explode('/', $contentType, 2);

            if (count($availableParts) < 2 || count($providedParts) < 2) {
                continue;
            }

            if ($availableParts[0] === $providedParts[0] && $availableParts[1] === '*') {
                return true;
            }
        }

        return false;
    }

    private function removeDirectives(string $fullContentType): string
    {
        return explode(';', $fullContentType, 2)[0];
    }
}
