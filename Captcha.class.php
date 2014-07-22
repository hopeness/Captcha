<?php
/**
 * 通用验证码生成类
 **/
class Captcha {

    /**
     * 对象
     */
    private $obj;

    /**
     * 颜色域
     */
    // private $color = array(
    //         array('bg' => array(70, 100, 100), 'font' => array(255, 255, 255)),
    //         array('bg' => array(0, 0, 0), 'font' => array(255, 255, 255))
    //     );

    /**
     * 字体路径
     */
    // private $fontDir = dirname(__FILE__) . '/font/';

    public function __construct($width = 80, $height = 35, $fontSize = null, $overlap = 0.7, $angle = 30){
        $this->obj = new Captcha_GD($width, $height, $fontSize, $overlap, $angle);
    }

    public function show($code){
        $this->obj->show($code);
    }

}


/**
 * GD验证码生成类
 **/
class Captcha_GD {

    /**
     * 画布资源
     **/
    private $image;

    /**
     * 验证码字符串
     **/
    private $code;

    /**
     * 字符串总数
     **/
    private $codeCount;

    /**
     * 图像宽度
     **/
    private $width;

    /**
     *图像高度
     **/
    private $height;

    /**
     * 图像背景色
     **/
    private $bgColor;

    /**
     * 字体大小
     **/
    private $fontSize;

    /**
     * 字体颜色
     **/
    private $fontColor;

    /**
     * 字体重叠比
     **/
    private $overlap;

    /**
     * 字体文件地址
     **/
    private $font;

    /**
     * 字体包路径
     **/
    private $fontDir;

    /**
     * 字体库
     **/
    private $fontArray;

    /**
     * 向左最大旋转角度
     **/
    private $maxAngle;

    /**
     * 向右旋转角度
     **/
    private $minAngle;

    public function __construct($width = 80, $height = 35, $fontSize = null, $overlap = 0.7, $angle = 30){
        $this->width = $width;
        $this->height = $height;
        $this->fontSize = $fontSize;
        $this->overlap = $overlap;
        $this->maxAngle = $angle;
        $this->minAngle = -$angle;
        $this->fontDir = dirname(__FILE__) . '/font/';
    }

    public function show($code){
        $this->code = $code;
        $this->codeCount = strlen($this->code);
        // 如果字体大小没有被设置，则按照长宽和字符个数自动计算
        if($this->fontSize === null){
            $fontSize = $this->width / $this->codeCount;
            if($fontSize < $this->height){
                $this->fontSize = $fontSize * 1.2;
            }else{
                $this->fontSize = $this->height * 1.2;
            }
        }

        $this->image = imagecreate($this->width, $this->height);
        $this->bgColor = imagecolorallocate($this->image, 70, 100, 100);
        $this->fontColor = imagecolorallocate($this->image, 255, 255, 255);
        // 调试情况下开启，计算字符边框
        $this->border = imagecolorallocate($this->image, 0, 0, 0);
        $this->font = $this->randomFont();

        // 为每个字符单独设置旋转角度、大小等
        $left = 0;
        for($i=0; $i<$this->codeCount; $i++){
            $angle = rand($this->maxAngle, $this->minAngle);
            $ttfbbox = imagettfbbox($this->fontSize, $angle, $this->font, $this->code[$i]);
            
            // 计算图形在象限中的4个极值
            $x = $y = array();
            for($j=0; $j<8; $j++){
                if($j%2 == 0){
                    if(!isset($x['min']) || $ttfbbox[$j] < $x['min']){
                        $x['min'] = $ttfbbox[$j];
                    }
                    if(!isset($x['max']) || $ttfbbox[$j] > $x['max']){
                        $x['max'] = $ttfbbox[$j];
                    }
                }else{
                    if(!isset($y['min']) || $ttfbbox[$j] < $y['min']){
                        $y['min'] = $ttfbbox[$j];
                    }
                    if(!isset($y['max']) || $ttfbbox[$j] > $y['max']){
                        $y['max'] = $ttfbbox[$j];
                    }
                }
            }

            // 计算图形边长
            $width = abs($x['min'] - $x['max']);
            $height = abs($y['min'] - $y['max']);

            // 图像边缘距离
            $top = ($this->height - $height) / 2 + abs($y['min']);
            if($left == 0){
                $left = abs($x['min']);
            }else{
                $left = ($left + $width * $this->overlap);
            }

            $border = imagettftext($this->image, $this->fontSize, $angle, $left, $top, $this->fontColor, $this->font, $this->code[$i]);

            // 调试信息
            // 字符坐标排查，插入自定义header
            // header('X-test1: '.$angle.' | '.$width.' | '.$height.' | '.$left.' | '.$top.' |$| (x'.$border[0].' y'.$border[1].') | (x'.$border[2].' y'.$border[3].') | (x'.$border[4].' y'.$border[5].') | (x'.$border[6].' y'.$border[7].')');
            // header('X-test2: '.$angle.' | '.$width.' | '.$height.' | '.$left.' | '.$top.' |$| (x'.$x['min'].' y'.$y['min'].') | (x'.$x['max'].' y'.$y['min'].') | (x'.$x['max'].' y'.$y['max'].') | (x'.$x['min'].' y'.$y['max'].')');
            // header('X-font: '.$this->font);

            // 字符位置辅助代码
            // $x['min'] = $x['min'] + $left;
            // $x['max'] = $x['max'] + $left;
            // $y['min'] = $y['min'] + $top;
            // $y['max'] = $y['max'] + $top;
            // imagepolygon($this->image, $border, 4, $this->border);
            // imagepolygon($this->image, array($x['min'], $y['min'], $x['max'], $y['min'], $x['max'], $y['max'], $x['min'], $y['max']), 4, $this->border);
        }

        header('Content-Type: image/png');
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header('Expires: 0');
        imagepng($this->image);
    }

    private function randomFont(){
        if(empty($this->fontArray)){
            if(false != ($handle = opendir($this->fontDir))){
                $i=0;
                while(false !== ($file = readdir($handle))){
                    // 只保留ttf后缀文件
                    if(strpos($file,".ttf")){
                        $fileArray[$i] = $this->fontDir.$file;
                        $i++;
                    }
                }
                closedir($handle);
            }
        }
        $key = array_rand($fileArray);
        return $fileArray[$key];
    }

    public function __destruct(){
        if($this->image){
            imagedestroy($this->image);
        }
    }

}


/**
 * Imagic验证码生成类
 **/
class Captcha_Imagick {
    
}
