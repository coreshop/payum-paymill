<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2020 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Payum\Paymill\Action;

use CoreShop\Payum\Paymill\Api;
use CoreShop\Payum\Paymill\Request\ObtainToken;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\RenderTemplate;

/**
 * @property Api $api
 */
class ObtainTokenAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;

    /**
     * @var string
     */
    protected $templateName;

    /**
     * @param string $templateName
     */
    public function __construct($templateName)
    {
        $this->apiClass = Api::class;
        $this->templateName = $templateName;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request ObtainToken */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $getHttpRequest = new GetHttpRequest();
        $this->gateway->execute($getHttpRequest);
        if ($getHttpRequest->method === 'POST' && isset($getHttpRequest->request['paymillToken'])) {
            $model['paymillToken'] = $getHttpRequest->request['paymillToken'];

            return;
        }

        $this->gateway->execute($renderTemplate = new RenderTemplate($this->templateName, array(
            'model' => $model,
            'paymillPublicKey' => $this->api->getPublicKey(),
            'amount' => $model['amount'],
            'currency' => $model['currency'],
            'email' => $model['email'],
        )));

        throw new HttpResponse($renderTemplate->getResult());
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof ObtainToken &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
