# Swipe Payum bundle for Sylius

The Payum extension to rapidly build new extensions.

1. Update composer.json:

```json
"require": {
    "php": "^7.1",
    "InfiniteSoftware/swipe-payum": "dev-master",
    ...
}
```

2. Create new project

```bash
$ composer install
```

3. Add this lines to your `app/config/services.yml` file:

```yaml
app.swipe.gateway:
        class: Payum\Core\Bridge\Symfony\Builder\GatewayFactoryBuilder
        arguments: [Payum\Swipe\SwipeGatewayFactory]
        tags:
            - { name: payum.gateway_factory_builder, factory: swipe }

    sylius.form.type.gateway_configuration.swipe:
        class: Payum\Swipe\Type\SwipeGatewayConfigurationType
        tags:
            - { name: sylius.gateway_configuration_type, type: swipe, label: Swipe }
            - { name: form.type }

    app.payum_action.capture:
       class: Payum\Swipe\Action\CaptureAction
       arguments:
            - "@sylius.payment_description_provider"
            - "@logger"
       tags:
            - { name: payum.action, prepend: true, all: false, factory: swipe, alias: sylius.swipe.capture }

    app.payum_action.convert_payment:
       class: Payum\Swipe\Action\ConvertPaymentAction
       arguments:
            - "@router"
       tags:
            - { name: payum.action, prepend: true, all: false, factory: swipe, alias: sylius.swipe.convert_payment }

    app.payum_action.notify:
       class: Payum\Swipe\Action\NotifyAction
       arguments:
            - "@sylius.repository.payment"
            - "@sm.factory"
            - "@logger"
            - "@sylius.manager.payment"
       tags:
            - { name: payum.action, prepend: true, all: false, factory: swipe, alias: sylius.swipe.notify }
```

## License

Swipe Payum bundle is released under the [MIT License](LICENSE).
