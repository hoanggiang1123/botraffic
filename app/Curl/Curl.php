<?php

namespace App\Curl;

class Curl
{
	private $site;
	private $url;
	private $path;
	protected static $header = [];


	public function __construct()
	{
	}

	private function setUrl($url)
	{
		$this->url = $url;
	}

	public function getUrl()
	{
		return $this->url;
	}

	public function getSite()
	{
		return $this->site;
	}

	private function pathCookie($url)
	{
		$this->setUrl($url);
		$this->site = $this->sliceSite($url);
		if ($this->site) {
			if (!file_exists(base_path().'/app/Curl/cookies/'. $this->site . '.txt')) {
				$thisFile = fopen(base_path().'/app/Curl/cookies/'. $this->site . '.txt', 'w+');
				if (!$thisFile) throw new Exception("Không thể tạo file!");
			}
			if (is_file(base_path().'/app/Curl/cookies/'. $this->site . '.txt')) {
				$this->path = base_path().'/app/Curl/cookies/'. $this->site . '.txt';
			}

		}
	}

	protected function setHeader($header = [])
	{
		if (count($header)) {
			foreach ($header as $key => $value) {
				self::$header[] = $value;
			}
		}
		return $this;
	}

	public function curlBot($url, $redirect = 2) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, "Google Mozilla/5.0 (compatible; Googlebot/2.1;)");
        if ($redirect) {
        	curl_setopt($ch, CURLOPT_MAXREDIRS, $redirect);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, "http://www.google.com/bot.html");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5000);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5000);
        $result = curl_exec($ch);
        return $result;
	}

	public function getContent($url, $header = [], $updateCookie = 0)
	{
		if ($header) {
			$this->setHeader($header);
		}
		$this->pathCookie($url);

		$ch = @curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, self::$header);
        curl_setopt($ch, CURLOPT_REFERER, "https://www.google.com.vn");
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		($this->path ? curl_setopt($ch, CURLOPT_COOKIEFILE, $this->path) : '');
		($updateCookie ? curl_setopt($ch, CURLOPT_COOKIEJAR, $this->path) : '');
		curl_setopt($ch, CURLOPT_TIMEOUT, 9999);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 9999);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$page = curl_exec($ch);
		curl_close($ch);
		return $page;
	}

	public function postContent($url, $data = '', $header = [])
	{
		// $this->pathCookie($url);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36');
		curl_setopt($ch, CURLOPT_HEADER, 0);
		// curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
		// curl_setopt($ch, CURLOPT_COOKIEJAR, $this->path);
		// curl_setopt($ch, CURLOPT_COOKIEFILE, $this->path);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 9999);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 9999);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		$return = curl_exec($ch);
		curl_close($ch);
		return $return;
	}


	private function sliceSite($url) {
		preg_match('/https?:\/\/(?:www\.)?(.+?)\./is', $url, $domainName);
		if ($domainName) {
			return $domainName[1];
		}

	}

    public function curlVoice ($content) {

        $voices = ['banmai', 'lannhi', 'myan', 'thuminh', 'linhsan', 'giahuy'];

        $key = array_rand($voices);

        $person = $voices[$key];

        $ch = curl_init('https://api.fpt.ai/hmi/tts/v5');

        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'api-key: Z1J49nqnYtgEtSDiqjafLwNGsUZJZwc2',
            'voice: ' . $person,
            'callback_url: https://api.tiencuocmienphi.com/api/text-to-speech-call-back'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 9999);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 9999);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $return = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) return null;

        return $return;
    }
}
