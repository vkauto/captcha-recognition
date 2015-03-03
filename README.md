# Распознавание капчи

```php
use VKauto\CaptchaRecognition\Captcha;

# Также первым аргументом можно передать одну из констант класса Captcha (Captcha::AntiCaptchaService, например)
$captcha = new Captcha('http://anti-captcha.com', 'API key');

$text = $captcha->recognize('http://i.imgur.com/Ni4NYbo.png');

if ($text != false)
{
  // ...
}
```
