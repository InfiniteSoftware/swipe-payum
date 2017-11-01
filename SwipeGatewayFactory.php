<?php
namespace Payum\Swipe;

use Payum\Swipe\Action\AuthorizeAction;
use Payum\Swipe\Action\CancelAction;
use Payum\Swipe\Action\ConvertPaymentAction;
use Payum\Swipe\Action\NotifyAction;
use Payum\Swipe\Action\RefundAction;
use Payum\Swipe\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Payum\Swipe\Action\SyncAction;

class SwipeGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => 'swipe',
            'payum.factory_title' => 'swipe',
//            'payum.action.capture' => new CaptureAction(),
//            'payum.action.sync'                        => new SyncAction(),
            'payum.action.authorize' => new AuthorizeAction(),
            'payum.action.refund' => new RefundAction(),
            'payum.action.cancel' => new CancelAction(),
//            'payum.action.notify' => new NotifyAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
        ]);
        $config['payum.default_options'] = array(
            'sandbox' => false,
        );
        $config->defaults($config['payum.default_options']);
        $config['payum.required_options'] = [];
        $config['payum.api'] = function (ArrayObject $config) {
            $config->validateNotEmpty($config['payum.required_options']);
            return new Api((array) $config, $config['payum.http_client'], $config['httplug.message_factory']);
        };
    }
}
