<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2017 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Payum\Paymill;

use GuzzleHttp\Psr7\Request;
use Paymill\API\CommunicationAbstract;
use Payum\Core\Bridge\Guzzle\HttpClientFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\HttpClientInterface;

class Api extends CommunicationAbstract
{
    const API_URL = 'https://api.paymill.com/v2.1/';

    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var array
     */
    protected $options = [
        'private_key' => null,
        'public_key' => null
    ];

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     * @throws \Payum\Core\Exception\LogicException if a sandbox is not boolean
     */
    public function __construct(array $options, HttpClientInterface $client = null)
    {
        $options = ArrayObject::ensureArrayObject($options);

        $options->validatedKeysSet([
            'private_key',
            'public_key'
        ]);

        $this->options = $options;
        $this->client = HttpClientFactory::create();
    }

    /**
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->options['private_key'];
    }

    /**
     * @return string
     */
    public function getPublicKey()
    {
        return $this->options['public_key'];
    }

    /**
     * Perform HTTP request to REST endpoint
     *
     * @param string $action
     * @param array  $params
     * @param string $method
     * @return array
     */
    public function requestApi($action = '', $params = array(), $method = 'POST')
    {
        $headers = [];
        $request = new Request($method, $this->getApiEndpoint($action), $headers, http_build_query($params));

        $options = [
            'Authorization' => $this->getPrivateKey() .':',
        ];
        $response = $this->client->send($request, $options);

        $content = $response->getBody();
        $type = $response->getHeaderLine('Content-Type');

        if (false === ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $content = ['error' => $response->getReasonPhrase()];
        }

        if ('application/json' === $type) {
            $content = json_decode($content, true);
        } elseif ((false !== strpos(strtolower($type), 'text/csv')) && !isset($content['error'])) {
            return $content;
        }

        return [
            'header' => [
                'status' => $response->getStatusCode(),
                'reason' => $response->getReasonPhrase(),
            ],
            'body' => $content,
        ];
    }

    /**
     * @param string $action
     *
     * @return string
     */
    protected function getApiEndpoint($action)
    {
        return self::API_URL . $action;
    }
}
