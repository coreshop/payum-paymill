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

namespace CoreShop\Payum\Paymill;

use CoreShop\Payum\Paymill\Action\CaptureAction;
use CoreShop\Payum\Paymill\Action\ConvertPaymentAction;
use CoreShop\Payum\Paymill\Action\ObtainTokenAction;
use CoreShop\Payum\Paymill\Action\StatusAction;
use CoreShop\Payum\Paymill\Action\TransactionAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class PaymillGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => 'paymill',
            'payum.factory_title' => 'Paymill',
            'payum.action.capture' => new CaptureAction(),
            'payum.action.transaction' => new TransactionAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
            'payum.template.obtain_token' => '@PayumPaymill/Action/obtain_checkout_token.html.twig',
            'payum.action.api.heidelpay_obtain_token' => function (ArrayObject $config) {
                return new ObtainTokenAction($config['payum.template.obtain_token']);
            },
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = array(
                'private_key' => '',
                'public_key' => '',
            );
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = [];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api((array)$config, $config['payum.http_client']);
            };

            $config['payum.paths'] = array_replace([
                'PayumPaymill' => __DIR__.'/Resources/views',
            ], $config['payum.paths'] ?: []);
        }

    }
}
