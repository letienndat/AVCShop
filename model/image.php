<?php
class Image {
    public $id;
    public $product_id;
    public $title;
    public $path_image;

    public function __construct($id, $product_id, $title, $path_image) {
        $this->id = $id;
        $this->product_id = $product_id;
        $this->title = $title;
        $this->path_image = $path_image;
    }
}