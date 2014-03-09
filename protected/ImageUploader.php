<?php

class ImageUploader {

    private $files;
    private $maxFiles;
    private $maxSize = 5000000;

    public function __construct() {
        $this->files = $_FILES;
        $this->maxFiles = 5;
    }

    private function makeDir($postId) {
        $dir = "images";
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public function storeImages($postId) {
        if (count($this->files) > $this->maxFiles) {
            throw new Exception("Превышено ограничение на количество изображений");
        }

        $dir = $this->makeDir($postId);

        $i = 0;
        $files = $this->files;
        //выполняем необходимые проверки
        if (!isset($files['image'])) {
            return;
        }
        while (($files['image']['name'][$i]) &&
        ($files['image']['tmp_name'][$i] != 'none')) {
            $name = $files['image']['name'][$i];
            if ($files['image']['size'][$i] == 0) {
                throw new Exception("Изображение {$name} имеент нулевой размер");
            }
            if ($files['image']['size'][$i] > $this->maxSize) {
                throw new Exception("Изображение {$name}"
                . "превышает максимаольный размер в {$this->maxSize} байт");
            }
            if (!getimagesize($files['image']['tmp_name'][$i])) {
                throw new Exception("Файл {$name} не является изображением");
            }
            $destination = $dir . "/" . $name;

            if (!is_uploaded_file($files['image']['tmp_name'][$i])) {
                throw new Exception("File is not uploaded_file");
            }
            if (!move_uploaded_file($files['image']['tmp_name'][$i], $destination)) {
                throw new Exception('Невозможно переместить файл в каталог назначения');
            }
            $i++;
        }
        return true;
    }

}
