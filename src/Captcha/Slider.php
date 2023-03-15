<?php
/**
 * Author: lang
 * Email: 732853989@qq.com
 * Date: 2022/12/7
 * Time: 10:55
 */

namespace Kkokk\Poster\Captcha;

use Kkokk\Poster\Facades\Cache;
use Kkokk\Poster\Base\CaptchaBase;
use Kkokk\Poster\Interfaces\MyCaptcha;

class Slider extends CaptchaBase implements MyCaptcha
{

    protected $configs = [
        'src' => '',
        'im_width' => 340,
        'im_height' => 251,
        'im_type' => 'png', // png 默认 jpg quality 质量
        'quality' => 80,    // jpg quality 质量
        'bg_width' => 340,
        'bg_height' => 191,
        'slider_width' => 50,
        'slider_height' => 50,
        'slider_border' => 2,
        'slider_bg' => 1,
    ];  // 验证码图片配置

    public function config($params = [])
    {
        if (empty($params)) return $this;
        if (PHP_VERSION < 7) {
            $this->configs['src'] = isset($params['src']) ? $params['src'] : $this->configs['src'];
            $this->configs['im_width'] = isset($params['im_width']) ? $params['im_width'] : $this->configs['im_width'];
            $this->configs['im_height'] = isset($params['im_height']) ? $params['im_height'] : $this->configs['im_height'];
            $this->configs['im_type'] = isset($params['im_type']) ? $params['im_type'] : $this->configs['im_type'];
            $this->configs['quality'] = isset($params['quality']) ? $params['quality'] : $this->configs['quality'];
            $this->configs['bg_width'] = isset($params['bg_width']) ? $params['bg_width'] : $this->configs['bg_width'];
            $this->configs['bg_height'] = isset($params['bg_height']) ? $params['bg_height'] : $this->configs['bg_height'];
            $this->configs['slider_width'] = isset($params['slider_width']) ? $params['slider_width'] : $this->configs['slider_width'];
            $this->configs['slider_height'] = isset($params['slider_height']) ? $params['slider_height'] : $this->configs['slider_height'];
            $this->configs['slider_border'] = isset($params['slider_border']) ? $params['slider_border'] : $this->configs['slider_border'];
            $this->configs['slider_bg'] = isset($params['slider_bg']) ? $params['slider_bg'] : $this->configs['slider_bg'];
        } else {
            $this->configs['src'] = $params['src'] ?? $this->configs['src'];
            $this->configs['im_width'] = $params['im_width'] ?? $this->configs['im_width'];
            $this->configs['im_height'] = $params['im_height'] ?? $this->configs['im_height'];
            $this->configs['im_type'] = $params['im_type'] ?? $this->configs['im_type'];
            $this->configs['quality'] = $params['quality'] ?? $this->configs['quality'];
            $this->configs['bg_width'] = $params['bg_width'] ?? $this->configs['bg_width'];
            $this->configs['bg_height'] = $params['bg_height'] ?? $this->configs['bg_height'];
            $this->configs['slider_width'] = $params['slider_width'] ?? $this->configs['slider_width'];
            $this->configs['slider_height'] = $params['slider_height'] ?? $this->configs['slider_height'];
            $this->configs['slider_border'] = $params['slider_border'] ?? $this->configs['slider_border'];
            $this->configs['slider_bg'] = $params['slider_bg'] ?? $this->configs['slider_bg'];
        }

        return $this;
    }

    public function get($expire = 0)
    {

        $data = $this->draw();

        $this->imOutput(
            $this->im,
            __DIR__ . '/../../tests/poster/im.' . $this->configs['im_type'],
            $this->configs['im_type'],
            $this->configs['quality']
        );

        $baseData = $this->baseData($this->im, $this->configs['im_type']);

        $key = uniqid('slider' . mt_rand(0, 9999), true);

        $res = [
            'img' => $baseData,
            'key' => $key,
            'y' => $data['y'],
        ];

        $setCache = $this->setCache($key, $data['x'], $expire);
        if(!$setCache) $res['secret'] = $data['x'];

        return $res;
    }

    /**
     * 判断是否正确
     * 目前使用的是 laravel 的 cache
     * Author: lang
     * Email: 732853989@qq.com
     * Date: 2022/12/7
     * Time: 11:44
     * @param $key
     * @param $value
     * @param int $leeway
     * @return bool
     */
    public function check($key, $value, $leeway = 0, $secret = null)
    {
        $x = $this->getCache($key) ? : $secret;

        if (empty($x) ) return false;

        $leeway = $leeway ?: $this->leeway;

        return $x >= ($value - $leeway) && $x <= ($value + $leeway);
    }

    // 实现图片绘制
    public function draw()
    {

        $im_width = $this->configs['im_width'];
        $im_height = $this->configs['im_height'];

        $this->im = $this->PosterBase->createIm($im_width, $im_height, [], true);

        $this->drawImage($this->configs['src']); // 添加bg图片

        $bg_width = $this->configs['bg_width'];
        $bg_height = $this->configs['bg_height'];

        $slider_width = $this->configs['slider_width'];
        $slider_height = $this->configs['slider_height'];
        $border = $this->configs['slider_border'];

        $w = $slider_width - $border;
        $h = $slider_height - $border;

        $bg = $this->PosterBase->createIm($slider_width, $slider_height, [0, 0, 0, 60], true); // 创建阴影背景
        $ims = $this->PosterBase->createIm($slider_width, $slider_height, [], false); // 创建抠图背景

        $x1 = mt_rand(30, $bg_width - $w);
        $x2 = $x1 + $w;

        $y1 = mt_rand(0, $bg_height - $h);
        $y2 = $y1 + $h;

        for ($i = 0; $i < $bg_width; $i++) {
            for ($j = 0; $j < $bg_height; $j++) {
                // 矩形抠图
                if (($i < $x2 && $i >= $x1) && ($j < $y2 && $j >= $y1)) {
                    $rgbColor = imagecolorat($this->im, $i, $j);
                    imagesetpixel($ims, $i - $x1 + $border / 2, $j - $y1 + $border / 2, $rgbColor); // 抠图
                }
                // 利用叉积抠图 p1 p2 p3 可以抠多边形
                // ...
            }
        }

        imagecopy($this->im, $bg, $x1, $y1, 0, 0, $slider_width, $slider_height);

        $bgCount = 1;
        $maxCount = min($this->configs['slider_bg'], 4);
        $maxCount = max($maxCount, 1);
        while ($bgCount < $maxCount) {
            // 额外滑块背景
            $x = mt_rand(30, $bg_width - $w);
            $y = mt_rand(0, $bg_height - $h);
            imagecopy($this->im, $bg, $x, $y, 0, 0, $slider_width, $slider_height);
            $bgCount++;
        }

        imagecopy($this->im, $ims, 5, 196, 0, 0, $slider_width, $slider_width);

        $this->destroyImage($bg);
        $this->destroyImage($ims);

        return [
            'x' => $x1,
            'y' => $y1,
        ];
    }

    protected function getImBg()
    {
        return __DIR__ . '/../style/slider_bg/layer0' . mt_rand(1, 3) . '.jpg';
    }

}
