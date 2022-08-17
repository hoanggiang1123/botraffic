<?php

namespace App\CrawMeta;

use App\Curl\Curl;
use App\Curl\Dom;

class CrawMeta {

    protected $parser;

    protected $curl;

    public function __construct() {

        $this->curl = (new Curl);
        $this->parser = (new Dom);
    }

    public function getMeta($url) {

        $content = $this->curl->getContent($url, [], true);

        $html = $this->parser->str_get_html($content);

        $title = $description = $image = $keywords = '';

        if ($html) {
            $title = $html->find('title', 0) ? $html->find('title', 0)->text() : '';
            $description = $html->find('meta[name=description]', 0) ? $html->find('meta[name=description]', 0)->getAttribute('content') : '';
        }

        return [
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'keywords' => $keywords
        ];
    }
}
