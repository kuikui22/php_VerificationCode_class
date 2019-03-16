<?php
/**
 * 驗證碼
 * 
 * @author bess
 * @since 2019/03
 */
class VerificationCode {
    private $width; //圖片寬度
    private $height; //圖片高度
    private $code_num; //數字數量
    // private $pic_type; //圖片類型(須注意輸入類型,並統一轉換固定類型大小寫)
    private $font_size; //文字尺寸 (可設rand(20,25) 查看min與max的差異)
    private $font; //字型
    private $code_txt; //驗證碼文字
    private $image; //驗證碼畫布

    /**
     * 初始化參數
     * 
     * @param int $width 圖片寬度
     * @param int $height 圖片高度
     * @param int $code_num 數字數量
     * 
     * PHP获取绝对路径dirname(__FILE__) 與 __DIR__ 比較:
     * https://www.awaimai.com/408.html
     * 
     * @return void
     */
    function __construct($width=150, $height=60, $code_num=4,$font_size=20) {
        $this->width = $width;
        $this->height = $height;        
        $this->code_num = $code_num; 
        $this->font_size = $font_size; 
        $this->font = __DIR__.'/fonts/Lato-Black.ttf';
        $this->code_txt = $this->makeCode();
    }

    /**
     * 顯示驗證碼圖片
     * 繪出背景
     * 輸出文字
     * 繪出干擾點
     * 設置圖片類型並繪出圖片
     * 
     * @return void
     */
    function showImage() {
        $this->createImage();
        $this->drawText();
        $this->drawDisturb();
        $this->drawCaptcha();
    }

    /** 
     * 取得驗證碼數字(存session使用)
     * 
     * @return void
     */
    function getCodeTxt() {
        return $this->code_txt;
    }

    /**
     * 重新生成驗證碼文字(js點擊刷新使用)
     * 
     * @return void
     */
    function reMakeCode() {
        $this->code_txt = $this->makeCode();
    }

    /**
     * 生成驗證碼文字
     * 文字組成: 0-9,a-z,A-Z
     * 使用ASCII碼做隨機數,並轉換
     *   0-9: 48-57
     *   a-z: 97-122
     *   A-Z: 65-90
     * 
     * rand 與 mt_rand 的差異: 
     * https://www.jb51.net/article/32836.htm
     * http://www.111cn.net/phper/php-function/49135.htm
     * 
     * @return string 驗證碼文字
     */
    private function makeCode() {
        $str_num = '';
        
        for($i=0; $i < $this->code_num; $i++) { 
            $number = mt_rand(0,2);
            switch($number) {
                case 0: //數字
                    $txt = chr(mt_rand(48,57));
                    break;
                case 1: //小寫英文
                    $txt = chr(mt_rand(97,122));
                    break;
                case 2: //大寫英文
                    $txt = chr(mt_rand(65,90));
                    break;
            }

            $str_num.=$txt;
        }

        return $str_num;
    }

    /**
     * 初始畫布,繪出背景
     * 背景色作法可隨機或制定幾種顏色選,這裡採用隨機
     * 
     * imagecreatetruecolor 與 imagecreate 差異
     * https://blog.csdn.net/dreamboycx/article/details/8490809
     * https://www.itread01.com/content/1541167226.html
     * https://www.twblogs.net/a/5b905c142b71776722194d9f
     *
     * @return void
     */
    private function createImage() {
        $this->image = imagecreate($this->width, $this->height);
        $bg_color = imagecolorallocate($this->image,rand(0,100),rand(0,100),rand(0,100));

        //繪出邊框
        $border_color = imagecolorallocate($this->image,rand(0,255),rand(0,255),rand(0,255));
        imagefilledrectangle($this->image, 1, 1, ($this->width-2), ($this->height-2), $border_color);
    }

    /**
     * 繪出文字
     * 高度,間距,顏色,大小隨機
     * 一般繪製驗證文字可使用imagechar,imagestring,imagettftext
     * 但這裡要調整文字的字形及大小故使用imagettftext
     * 
     * imagechar 與 imagestring 差異(見imageString() 函數):
     * http://www.jollen.org/php/jollen_php_book_68_php.html
     * 
     * imagettftext因font路徑錯誤無法載入圖片
     * http://php.net/manual/en/function.imagettftext.php
     * https://stackoverflow.com/questions/31905603/php-imagettftext-could-not-find-open-font-with-image-with-text-php-library?lq=1
     * 
     * 关于PHP中GD库函数imagettfbbox的坐标: 
     * https://zhuanlan.zhihu.com/p/34188170
     * 
     * @return void
     */
    private function drawText() {
        putenv('GDFONTPATH='.realpath('.'));

        for($i=0; $i < $this->code_num; $i++) { 
            $font_color = imagecolorallocate($this->image,rand(0,255),rand(0,128),rand(0,255));
            $x = ($this->width/$this->code_num) * $i + rand(1,4);
            $y = ($this->height/1.5)+rand(0,8);

            imagettftext($this->image, $this->font_size, rand(-30,30), $x, $y, $font_color, $this->font, $this->code_txt[$i]);

            // imagestring($this->image,rand(3,5),$x,$y,$this->code_txt[$i],$font_color);
            // imagechar($this->image,rand(3,5),$x,$y,$this->code_txt[$i],$font_color);
        }
        
    }

    /**
     * 繪出干擾圖像
     * 可繪出線條,雪花,干擾像素
     * 
     * 參考:
     * https://itw01.com/29OQE85.html
     * https://codertw.com/%E7%A8%8B%E5%BC%8F%E8%AA%9E%E8%A8%80/215108/
     * http://php.net/manual/en/function.imagesetpixel.php #像素畫圖(php文檔)
     * 
     * @return void
     */
    private function drawDisturb() {
        
        //線條(預設4條)
        for($i=0; $i < 4; $i++) { 
            $rand_color = imagecolorallocate($this->image,rand(0,255),rand(0,255),rand(0,255));
            imageline($this->image, rand(0,$this->width), rand(0,$this->height), rand(0,$this->width), rand(0,$this->height), $rand_color);
        }

        //雪花(預設5點)
        for($i=0; $i < 5; $i++) { 
            $rand_color = imagecolorallocate($this->image,rand(200,255),rand(200,255),rand(200,255));
            imagestring($this->image, rand(1,5), rand(0,$this->width), rand(0,$this->height), '*',$rand_color);
        }

        //干擾像素(預設100點)
        for($i=0; $i < 100; $i++) { 
            $rand_color = imagecolorallocate($this->image,rand(0,255),rand(0,255),rand(0,255));
            imagesetpixel($this->image, rand(1,$this->width-2), rand(1,$this->height-2), $rand_color);
        }
    }

    /**
     * 設置圖片類型並輸出圖片
     * 斷php系統支不支援此類型圖片
     * 判斷 JPEG, PNG, GIF
     * 若不支援則輸出錯誤並退出程序
     * 
     * imagetypes 用法:
     * http://php.net/manual/en/function.imagetypes.php
     * 
     * @return void
     */
    private function drawCaptcha() {
        if(imagetypes() & IMG_PNG) {
            header('Content-type:image/png');
            Imagejpeg($this->image);
        }else if(imagetypes() & IMG_JPG) {
            header('Content-type:image/jpeg');
            Imagepng($this->image);
        }else if(imagetypes() & IMG_GIF) {
            header('Content-type:image/gif');
            Imagegif($this->image);
        }else {
            die('圖片格式不支援!');
        }
    }
    

    /**
     * 銷毀圖片資源
     * 
     * @return void
     */
    function __destruct() {
        imagedestroy($this->image);
    }
}