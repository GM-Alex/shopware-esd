<?php declare(strict_types=1);

namespace Sas\Esd\Storefront\Event;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\EventData\ArrayType;
use Shopware\Core\Framework\Event\EventData\EntityType;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;
use Shopware\Core\Framework\Event\EventData\ScalarValueType;
use Shopware\Core\Framework\Event\MailActionInterface;
use Shopware\Core\Framework\Event\SalesChannelAware;
use Symfony\Contracts\EventDispatcher\Event;

class EsdSerialPaymentStatusPaidEvent extends Event implements MailActionInterface, SalesChannelAware
{
    public const EVENT_NAME = 'esd.serial.payment.status.paid';

    /**
     * @var Context
     */
    private $context;

    /**
     * @var OrderEntity
     */
    private $order;

    /**
     * @var array
     */
    private $templateData;

    /**
     * @var MailRecipientStruct|null
     */
    private $mailRecipientStruct;

    /**
     * EsdPaymentStatusPaidEvent constructor.
     * @param Context $context
     * @param OrderEntity $order
     * @param array $templateData
     */
    public function __construct(
        Context $context,
        OrderEntity $order,
        array $templateData = []
    ) {
        $this->context = $context;
        $this->order = $order;
        $this->templateData = $templateData;
    }

    /**
     * @return OrderEntity
     */
    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    /**
     * @return array
     */
    public function getEsdSerials(): array
    {
        if (empty($this->templateData['esdSerials'])) {
            return [];
        }

        $esdSerials = $this->templateData['esdSerials'];
        usort($esdSerials, function($a, $b) {
            return $a['productName'] <=> $b['productName'];
        });

        return $esdSerials;
    }

    /**
     * @return EventDataCollection
     */
    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('order', new EntityType(OrderDefinition::class))
            ->add('esdSerials', new ArrayType(new ScalarValueType(ScalarValueType::TYPE_STRING)));
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    /**
     * @return MailRecipientStruct
     */
    public function getMailStruct(): MailRecipientStruct
    {
        if (!$this->mailRecipientStruct instanceof MailRecipientStruct) {
            $this->mailRecipientStruct = new MailRecipientStruct([
                $this->order->getOrderCustomer()->getEmail() => $this->order->getOrderCustomer()->getFirstName() . ' ' . $this->order->getOrderCustomer()->getLastName(),
            ]);
        }

        return $this->mailRecipientStruct;
    }

    /**
     * @return string
     */
    public function getSalesChannelId(): string
    {
        return $this->order->getSalesChannelId();
    }

    /**
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->context;
    }
}
