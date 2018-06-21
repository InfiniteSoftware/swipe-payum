<?php
namespace Payum\Swipe\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Notify;
use Payum\Swipe\Api;
use SM\Factory\FactoryInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bridge\Monolog\Logger;
use \Doctrine\ORM\EntityManager;

class NotifyAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait;
    use ApiAwareTrait {
        setApi as _setApi;
    }

    /**
     * @var PaymentRepositoryInterface
     */
    private $paymentRepository;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $manager;

    /**
     * @param PaymentRepositoryInterface $paymentRepository
     */
    public function __construct(
        PaymentRepositoryInterface $paymentRepository,
        FactoryInterface $factory,
        Logger $logger,
        EntityManager $manager
    )
    {
        $this->paymentRepository = $paymentRepository;
        $this->factory = $factory;
        $this->logger = $logger;
        $this->manager = $manager;
        $this->apiClass = Api::class;
    }

    /**
     * {@inheritDoc}
     *
     * @param Request $request
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \SM\SMException
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $order = $request->getModel()->request->all();

        if ($order === null) {
            return;
        }
        $base = $this->paymentRepository->findByState(PaymentInterface::STATE_NEW);
        $order = $request->getModel()->request->all();
        if ($order === null) {
            return;
        }
        $found = null;
        foreach ($base as $item) {
            if (array_key_exists('comment', $item->getDetails())) {
                if ($item->getDetails()['comment'] === $order['comment']) {
                    $found = $item;
                }
            }
        }

        if ($found) {
            $this->logger->critical('trying to fraud a payment!', ['Payum']);
        } else {
            $this->logger->addInfo('order has paid', ['Payum']);
        }
    }

    public function setApi($api)
    {
        if (false == $api instanceof Api) {
            throw new UnsupportedApiException('Not supported API class.');
        }

        $this->_setApi($api);
    }


    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return $request instanceof Notify;
    }
}
