<?php
class Product {
    public $id;
    public $title;
    public $price;
    public $quantity;
    public $type;
    public $brand;
    public $manufacture;
    public $material;
    public $description;

    public function __construct(
        $title, // Tham số bắt buộc
        $type, 
        $brand, 
        $manufacture, 
        $material, 
        $description, 
        $id = null, // Tham số tùy chọn
        $price = null,
        $quantity = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->price = $price;
        $this->quantity = $quantity;
        $this->type = $type;
        $this->brand = $brand;
        $this->manufacture = $manufacture;
        $this->material = $material;
        $this->description = $description;
    }
}
