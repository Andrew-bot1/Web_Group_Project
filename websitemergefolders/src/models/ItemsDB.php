<?php
class ItemsDB
{
	private PDO $conn;
	
	public function __construct(PDO $connection)
	{
		$this->conn = $connection;
	}

	public function createItem($name, $price)
	{
		    $stmt = $this->conn->prepare("INSERT INTO items (name, price) VALUES (:name, :price)");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':price', $price);
            $stmt->execute();
            return (int)$this->conn->lastInsertId();  //returns the new item ID 
	}
	
	public function updateItem($id, $name, $price)
	{
		    $stmt = $this->conn->prepare("UPDATE items SET name = :name, price = :price WHERE item_id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':price', $price);
            $stmt->execute();

	}
	
	public function deleteItem($id)
	{
        $stmt = $this->conn->prepare("DELETE FROM items WHERE item_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

		
	}
	public function getAllItems()
	{
		$stmt = $this->conn->query("SELECT * FROM items");
		return $stmt->fetchAll();
	}
}
?>
	