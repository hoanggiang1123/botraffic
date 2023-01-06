<?php

namespace App\Observers;

use App\Models\Keyword;

use Illuminate\Support\Facades\Cache;

class KeywordObserver
{
    public function created (Keyword $keyword) {
        $keywords = Keyword::where('status', 1)->where('approve', 1)->get();

        $domains = [];

        foreach( $keywords as $keyword )
        {
            $url = $this->getHostNameFromUrl($keyword->url);

            if ($url && !in_array($url, $domains))
            {
                $domains[] = $url;
            }
        }

        Cache::put('total_domain', count($domains));
    }

    public function updated (Keyword $keyword) {
        $keywords = Keyword::where('status', 1)->where('approve', 1)->get();

        $domains = [];

        foreach( $keywords as $keyword )
        {
            $url = $this->getHostNameFromUrl($keyword->url);

            if ($url && !in_array($url, $domains))
            {
                $domains[] = $url;
            }
        }

        Cache::put('total_domain', count($domains));
    }

    public function getHostNameFromUrl ($input) {

        $input = trim($input, '/');

        if (!preg_match('#^http(s)?://#', $input)) {
            $input = 'http://' . $input;
        }

        $urlParts = parse_url($input);

        if (isset($urlParts['host'])) {
            $domain_name = preg_replace('/^www\./', '', $urlParts['host']);

            $check = explode('.', $domain_name);

            if (count($check) > 2) {
                return $check[1] . '.' . $check[2];
            }

            return $domain_name;
        }

        return '';

    }
}
