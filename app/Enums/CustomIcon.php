<?php

namespace App\Enums;

enum CustomIcon: string
{
    case Pdf = 'pdf-download';
    case Excel = 'excel';

    public function svg(): string
    {
        static $cache = [];

        return $cache[$this->value] ??= $this->read();
    }

    private function read(): string
    {
        $path = public_path("icons/{$this->value}.svg");
        $contents = is_file($path) ? file_get_contents($path) : false;

        return $contents === false ? '' : $contents;
    }
}
