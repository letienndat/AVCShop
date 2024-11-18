<?php
class Product {
    public $id;
    public $title;
    public $price;
    public $type;
    public $brand;
    public $manufacture;
    public $material;
    public $description;

    public function __construct($id, $title, $price, $type, $brand, $manufacture, $material, $description) {
        $this->id = $id;
        $this->title = $title;
        $this->price = $price;
        $this->type = $type;
        $this->brand = $brand;
        $this->manufacture = $manufacture;
        $this->material = $material;
        $this->description = $description;
    }
}
