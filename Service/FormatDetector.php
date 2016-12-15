<?php

namespace Paysera\Bundle\RestBundle\Service;

use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Paysera\Bundle\RestBundle\Exception\ApiException;

class FormatDetector
{
    /**
     * Returns requested format for encoding response.
     * First look to _format from routing, then checks Accept header. If no such header was found, returns default
     * format. If no format is supported that is defined in the Accept header, exception is thrown.
     *
     * @param Request  $request
     * @param string[] $formats
     *
     * @return string
     *
     * @throws ApiException        with code not_acceptable
     */
    public function getResponseFormat(Request $request, $formats)
    {
        $extension = $request->get('_format');
        if ($extension !== null && in_array($extension, $formats)) {
            return $extension;
        } else {
            $acceptHeader = $request->headers->get('Accept');
            if ($acceptHeader === null) {
                return reset($formats);
            }

            $accept = AcceptHeader::fromString($acceptHeader);
            foreach ($accept->all() as $item) {
                $mimetype = $item->getValue();
                if ($mimetype === '*/*') {
                    return reset($formats);
                }
                $format = $request->getFormat($mimetype);
                if ($format !== null && in_array($format, $formats)) {
                    return $format;
                }
            }
            throw new ApiException(ApiException::NOT_ACCEPTABLE, 'API was unable to encode response in any of your supported formats');
        }
    }

    /**
     * Returns format of data in request (using Content-Type header)
     *
     * @param Request  $request
     * @param string[] $formats
     *
     * @return string
     *
     * @throws ApiException        with code not_acceptable
     */
    public function getRequestFormat(Request $request, $formats)
    {
        $additionalFormats = array(
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
        );

        $contentTypeHeader = $request->headers->get('Content-Type');
        $extension = $request->attributes->get('_format');
        if (empty($contentTypeHeader) && empty($extension)) {
            return reset($formats);
        } else {
            if (array_key_exists($contentTypeHeader, $additionalFormats)) {
                return $additionalFormats[$contentTypeHeader];
            }

            $format = $request->getFormat($contentTypeHeader);

            if ($format === null) {
                $format = $extension;
            }

            if ($format === null || !in_array($format, $formats)) {
                throw new ApiException(
                    ApiException::NOT_ACCEPTABLE,
                    'Content-Type of your request is not supported: ' . $contentTypeHeader
                );
            }
            return $format;
        }
    }
}
