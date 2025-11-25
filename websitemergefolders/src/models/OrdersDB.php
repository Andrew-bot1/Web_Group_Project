<?php
//OrdersDB.php

class OrdersDB
{
    private PDO $conn;

    public function __construct(PDO $connection)
    {
        $this->conn = $connection;
    }

    public function createOrder($customer_name, $item_id, $quantity)
    {
        $stmt = $this->conn->prepare("INSERT INTO orders (customer_name, item_id, quantity) 
                                    VALUES (:customer_name, :item_id, :quantity)");
        $stmt->bindParam(':customer_name', $customer_name);
        $stmt->bindParam(':item_id', $item_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->execute();
        }
    

    public function updateOrder($order_id, $quantity)
    {
        $stmt = $this->conn->prepare("UPDATE orders SET quantity = :quantity WHERE order_id = :order_id");
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':order_id', $order_id);

        $stmt->execute();
        }
    

    public function deleteOrder($order_id)
    {

        $stmt = $this->conn->prepare("DELETE FROM orders WHERE order_id = :order_id");
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        }

    

    public function getAllOrders()
    {
        $stmt = $this->conn->query("SELECT o.order_id, o.customer_name, o.quantity, 
                                    i.name as item_name, i.price FROM orders o 
                                    JOIN items i ON o.item_id = i.item_id");
        $orders = $stmt->fetchAll();
        return $orders ?: []; //return array of orders or a null array if emtpy

    }

    public function getItems($search_item)
    {
        $stmt = $this->conn->prepare("SELECT o.order_id, o.customer_name, o.quantity,
                                     i.name as item_name, i.price 
                                    FROM orders o JOIN items i ON o.item_id = i.item_id 
                                    WHERE o.item_id = :search_item");
        $stmt->bindParam(':search_item', $search_item);
        $stmt->execute();
        $orders = $stmt->fetchAll();
        return $orders ?: [];  //return empty array if $orders is empty
    }

    public function getCustomers($search_name)
    {
        $stmt = $this->conn->prepare("SELECT o.order_id, o.customer_name, o.quantity, i.name as item_name, i.price 
                                    FROM orders o 
                                    JOIN items i ON o.item_id = i.item_id 
                                     WHERE o.customer_name = :search_name");
        $stmt->bindParam(':search_name', $search_name);
        $stmt->execute();
        $orders = $stmt->fetchAll();
        return $orders ?: []; //return empty array if $orders is empty
    }

}
