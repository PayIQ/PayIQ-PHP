<?php


namespace PayIQ\XML\Objects;


class OrderInfo
{
    public $orderReference = $this->getOrderReference();
    public $orderItems = $this->get_order_items();
    public $currency = $this->order->get_order_currency();
    // Optional alphanumeric string to indicate the transaction category.
    // Enables you to group and filter the transaction log and reports based on a custom criterion of your choice.
    public $orderCategory = '';
    // Optional order description displayed to end‐user on the payment site.
    public $orderDescription = '';

}
