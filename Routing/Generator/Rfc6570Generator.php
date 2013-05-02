<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hautelook\TemplatedUriBundle\Routing\Generator;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Generator\ConfigurableRequirementsInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * UrlGenerator generates a URL template according to RFC6570 based on a set of routes.
 * @link https://tools.ietf.org/html/rfc6570
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Baldur Rensch <baldur.rensch@hautelook.com>
 *
 * @api
 */
class Rfc6570Generator extends UrlGenerator implements UrlGeneratorInterface, ConfigurableRequirementsInterface
{
    /**
     * @throws MissingMandatoryParametersException When some parameters are missing that mandatory for the route
     * @throws InvalidParameterException           When a parameter value for a placeholder is not correct because
     *                                             it does not match the requirement
     */
    protected function doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $referenceType, $hostTokens)
    {
        // These are needed for encoded URLs, such as /resize/{width}x{height}/image/
        $this->decodedChars['%7B'] = '{';
        $this->decodedChars['%7D'] = '}';

        $variables = array_flip($variables);
        $mergedParams = array_replace($defaults, $this->context->getParameters(), $parameters);

        // all params must be given
        if ($diff = array_diff_key($variables, $mergedParams)) {
            throw new MissingMandatoryParametersException(sprintf('Some mandatory parameters are missing ("%s") to generate a URL for route "%s".', implode('", "', array_keys($diff)), $name));
        }

        $url = '';
        $optional = true;
        foreach ($tokens as $token) {
            if ('variable' === $token[0]) {
                if (!$optional || !array_key_exists($token[3], $defaults) || (string) $mergedParams[$token[3]] !== (string) $defaults[$token[3]]) {
                    // check requirement
                    if (null !== $this->strictRequirements && !preg_match('#^'.$token[2].'$#', $mergedParams[$token[3]])) {
                        $message = sprintf('Parameter "%s" for route "%s" must match "%s" ("%s" given) to generate a corresponding URL.', $token[3], $name, $token[2], $mergedParams[$token[3]]);
                        if ($this->strictRequirements) {
                            throw new InvalidParameterException($message);
                        }

                        if ($this->logger) {
                            $this->logger->error($message);
                        }

                        return null;
                    }

                    $url = $token[1].$mergedParams[$token[3]].$url;
                    $optional = false;
                }
            } else {
                // static text
                $url = $token[1].$url;
                $optional = false;
            }
        }

        if ('' === $url) {
            $url = '/';
        }

        // the contexts base url is already encoded (see Symfony\Component\HttpFoundation\Request)
        $url = strtr(rawurlencode($url), $this->decodedChars);

        // the path segments "." and ".." are interpreted as relative reference when resolving a URI; see http://tools.ietf.org/html/rfc3986#section-3.3
        // so we need to encode them as they are not used for this purpose here
        // otherwise we would generate a URI that, when followed by a user agent (e.g. browser), does not match this route
        $url = strtr($url, array('/../' => '/%2E%2E/', '/./' => '/%2E/'));
        if ('/..' === substr($url, -3)) {
            $url = substr($url, 0, -2) . '%2E%2E';
        } elseif ('/.' === substr($url, -2)) {
            $url = substr($url, 0, -1) . '%2E';
        }

        $schemeAuthority = '';
        if ($host = $this->context->getHost()) {
            $scheme = $this->context->getScheme();
            if (isset($requirements['_scheme']) && ($req = strtolower($requirements['_scheme'])) && $scheme !== $req) {
                $referenceType = self::ABSOLUTE_URL;
                $scheme = $req;
            }

            if ($hostTokens) {
                $routeHost = '';
                foreach ($hostTokens as $token) {
                    if ('variable' === $token[0]) {
                        if (null !== $this->strictRequirements && !preg_match('#^'.$token[2].'$#', $mergedParams[$token[3]])) {
                            $message = sprintf('Parameter "%s" for route "%s" must match "%s" ("%s" given) to generate a corresponding URL.', $token[3], $name, $token[2], $mergedParams[$token[3]]);

                            if ($this->strictRequirements) {
                                throw new InvalidParameterException($message);
                            }

                            if ($this->logger) {
                                $this->logger->error($message);
                            }

                            return null;
                        }

                        $routeHost = $token[1].$mergedParams[$token[3]].$routeHost;
                    } else {
                        $routeHost = $token[1].$routeHost;
                    }
                }

                if ($routeHost !== $host) {
                    $host = $routeHost;
                    if (self::ABSOLUTE_URL !== $referenceType) {
                        $referenceType = self::NETWORK_PATH;
                    }
                }
            }

            if (self::ABSOLUTE_URL === $referenceType || self::NETWORK_PATH === $referenceType) {
                $port = '';
                if ('http' === $scheme && 80 != $this->context->getHttpPort()) {
                    $port = ':'.$this->context->getHttpPort();
                } elseif ('https' === $scheme && 443 != $this->context->getHttpsPort()) {
                    $port = ':'.$this->context->getHttpsPort();
                }

                $schemeAuthority = self::NETWORK_PATH === $referenceType ? '//' : "$scheme://";
                $schemeAuthority .= $host.$port;
            }
        }

        if (self::RELATIVE_PATH === $referenceType) {
            $url = self::getRelativePath($this->context->getPathInfo(), $url);
        } else {
            $url = $schemeAuthority.$this->context->getBaseUrl().$url;
        }

        // add a query string if needed
        $extra = array_diff_key($parameters, $variables, $defaults);
        if (is_array($extra) && !empty($extra)) {
            $url .= '?';
            foreach ($extra as $key => $value) {
                if (is_scalar($value)) {
                    $url .= '{&' . $key . '}';
                } elseif (is_array($value)) {
                    $url .= '{&' . $key . '%5B%5D*}';
                }
            }
        }

        return $url;
    }
}
