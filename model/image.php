<?php
class Image {
    public $id;
    public $product_id;
    public $title;
    public $path_image;

    public function __construct($path_image, $id = null, $product_id = null, $title = null) {
        $this->id = $id;
        $this->product_id = $product_id;
        $this->title = $title;
        $this->path_image = $path_image;
    }    
}