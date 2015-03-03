<?php

namespace VKauto\CaptchaRecognition;

use VKauto\Utils\Request;
use VKauto\Utils\Log;

class Captcha
{
	/**
	 * Сервисы распознавания каптчи
	 */
	const AntiCaptchaService	= 'http://anti-captcha.com';
	const AntiGateService		= 'http://antigate.com';
	const RIPCaptchaService 	= 'http://ripcaptcha.com/';
	const ruCaptchaService 		= 'https://rucaptcha.com';

	/**
	 * Ссылка на сервис, с которым класс работает в данный момент
	 * @var string
	 */
	protected $serviceURL;

	/**
	 * Ключ для работы с API вышеуказанного сервиса
	 * @var string
	 */
	protected $apikey;

	/**
	 * Баланс аккаунта
	 * @var float
	 */
	public $balance;

	public function __construct($serviceURL, $apikey)
	{
		$this->serviceURL = $serviceURL;
		$this->apikey = $apikey;
		$this->updateBalance();
	}

	/**
	 * Обновление баланса
	 */
	public function updateBalance()
	{
		$response = Request::get($this->serviceURL . "/res.php?action=getbalance&key={$this->apikey}");

		switch ($response)
		{
			case "ERROR_WRONG_USER_KEY":
			case "ERROR_KEY_DOES_NOT_EXIST":
				Log::write('Wrong api key', ['CaptchaRecognition', 'ERROR']);
				die;
		}

		$this->balance = $response;
	}

	/**
	 * Распозование текста на изображении
	 * @param  string $imageURL
	 * @return string|bool
	 */
	public function recognize($imageURL)
	{
		$image = $this->getImage($imageURL);
		$captchaID = $this->uploadImage($image);

		if ($captchaID === false)
		{
			Log::write("Captcha image upload was failed.", ['CaptchaRecognition', 'ERROR']);
			return false;
		}

		Log::write("Captcha image was uploaded successfully. ID: {$captchaID}", ['CaptchaRecognition']);

		$captchaText = $this->getCaptchaText($captchaID);

		if ($captchaText === false)
		{
			Log::write("Captcha [{$captchaID}] recognition was failed.", ['CaptchaRecognition', 'ERROR']);
			return false;
		}
		else
		{
			Log::write("Captcha [{$captchaID}] was successfully recognized! Captcha text: {$captchaText}", ['CaptchaRecognition', 'SUCCESS']);
			return $captchaText;
		}
	}

	/**
	 * Получение base64 изображения по ссылке
	 * @param  string $imageURL
	 * @return string
	 */
	private function getImage($imageURL)
	{
		return base64_encode(Request::get($imageURL));
	}

	/**
	 * Загрузка изображения в очередь для распозавания
	 * @param  string $image
	 * @return int|bool
	 */
	private function uploadImage($image)
	{
		$response = Request::post($this->serviceURL . '/in.php',
		[
			'method' => 'base64',
			'key' => $this->apikey,
			'body' => $image
		]);

		if (strpos($response, '|'))
		{
			return explode('|', $response)[1];
		}
		else
		{
			return false;
		}
	}

	/**
	 * Проверка и запрос распознанного текста
	 * @param  int $captchaID
	 * @return string|bool
	 */
	private function getCaptchaText($captchaID)
	{
		for ($tries = 1; $tries <= 5; $tries++)
		{
			sleep(5);

			$response = Request::get($this->serviceURL . "/res.php?action=get&id={$captchaID}&key={$this->apikey}");

			if ($response == 'CAPCHA_NOT_READY')
			{
				Log::write("Captcha [{$captchaID}] isn't ready.", ['CaptchaRecognition']);
			}
			elseif (strpos($response, '|'))
			{
				return explode('|', $response)[1];
			}
		}

		return false;
	}
}
