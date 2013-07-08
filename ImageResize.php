<?php

/**
 * A class for performing resize and crop operations on images.
 *
 * @version 1.3.3 (2013-07-04)
 * @author Denis Komlev <deniskomlev@hotmail.com>
 */
class ImageResize
{
    /**
     * @var null|resource Currently loaded image resource
     */
    protected $image = null;

    /**
     * @var null|array Information about loaded image given by getimagesize() function
     */
    protected $image_info = null;

    /**
     * @var null|integer New width of image
     */
    protected $width = null;

    /**
     * @var null|integer New height of image
     */
    protected $height = null;

    /**
     * @var boolean Whether or not the image should be upscaled
     */
    public $upscale = false;

    /**
     * @var integer Quality for JPG images (0 is lowest, 100 is highest)
     */
    protected $quality_jpg = 80;

    /**
     * @var integer Quality for PNG images (0 is highest, 9 is lowest)
     */
    protected $quality_png = 2;

    /**
     * Constructor
     *
     * @param string $filename [optional] Full path to image file
     */
    public function __construct($filename = null)
    {
        if ($filename)
            $this->load($filename);
    }

    /**
     * Call the destroy() method on destruct for not keeping image in memory
     */
    public function __destruct()
    {
        $this->destroy();
    }

    /**
     * Destroy loaded image
     */
    public function destroy()
    {
        if (is_resource($this->image))
            imagedestroy($this->image);

        $this->image = null;
        $this->image_info = null;
    }

    /**
     * Load the image from file
     *
     * @param string @filename Full path to image
     * @return bool
     */
    public function load($filename)
    {
        $this->destroy();

        if (!$image_info = @getimagesize($filename))
            return false;

        $image_type = $image_info[2];

        if ($image_type == IMAGETYPE_JPEG)
            $this->image = @imagecreatefromjpeg($filename);
        elseif ($image_type == IMAGETYPE_GIF)
            $this->image = @imagecreatefromgif($filename);
        elseif ($image_type == IMAGETYPE_PNG)
            $this->image = @imagecreatefrompng($filename);

        if (is_resource($this->image)) {
            $this->image_info = $image_info;
            return true;
        }
        else {
            $this->image = null;
            return false;
        }
    }

    /**
     * Set new image width
     *
     * @param integer $value Width in pixels
     */
    public function width($value)
    {
        if (is_numeric($value) && $value > 0)
            $this->width = (int) $value;
        else
            $this->width = null;
    }

    /**
     * Set new image height
     *
     * @param integer $value Height in pixels
     */
    public function height($value)
    {
        if (is_numeric($value) && $value > 0)
            $this->height = (int) $value;
        else
            $this->height = null;
    }

    /**
     * Set size of image long side
     *
     * @param integer $value Size in pixels
     */
    public function setLongSide($value)
    {
        if (($width = $this->getWidth()) && ($height = $this->getHeight())) {
            if ($width >= $height)
                $this->width($value);
            else
                $this->height($value);
        }
    }

    /**
     * Set size of image short side
     *
     * @param integer $value Size in pixels
     */
    public function setShortSide($value)
    {
        if (($width = $this->getWidth()) && ($height = $this->getHeight())) {
            if ($width < $height)
                $this->width($value);
            else
                $this->height($value);
        }
    }

    /**
     * Reset image width and height
     */
    public function reset()
    {
        $this->width = null;
        $this->height = null;
    }

    /**
     * Set quality for JPG images
     *
     * @param integer $value Quality from 0 (lowest) to 100 (highest)
     */
    public function qualityJPG($value)
    {
        if (is_numeric($value) && ($value >= 0 && $value <= 100))
            $this->quality_jpg = (int) $value;
    }

    /**
     * Set quality for PNG images
     *
     * @param integer $value Quality from 0 (highest) to 9 (lowest)
     */
    public function qualityPNG($value)
    {
        if (is_numeric($value) && ($value >= 0 && $value <= 9))
            $this->quality_png = (int) $value;
    }

    /**
     * Get current image width
     *
     * @return integer
     */
    public function getWidth()
    {
        return is_resource($this->image) ? imagesx($this->image) : 0;
    }

    /**
     * Get current image height
     *
     * @return integer
     */
    public function getHeight()
    {
        return is_resource($this->image) ? imagesy($this->image) : 0;
    }

    /**
     * Get type of loaded image
     *
     * @return integer|null
     */
    public function getImageType()
    {
        if (is_resource($this->image))
            return $this->image_info[2];
        else
            return null;
    }

    /**
     * Calculate width based on height
     *
     * @param integer $height Image height
     * @return integer
     */
    public function getAutoWidth($height)
    {
        if ($this->getHeight()) {
            // Calculate the new width based on new height
            $ratio = $height / $this->getHeight();
            $width = $this->getWidth() * $ratio;
        }
        else {
            // Return current width if new height was not specified
            // or current height can not be obtained
            $width = $this->getWidth();
        }

        return (int) $width;
    }

    /**
     * Calculate height based on width
     *
     * @param integer $width Image width
     * @return integer
     */
    public function getAutoHeight($width)
    {
        if ($this->getWidth()) {
            // Calculate the new height based on new width
            $ratio = $width / $this->getWidth();
            $height = $this->getHeight() * $ratio;
        }
        else {
            // Return current height if new width was not specified
            // or current width can not be obtained
            $height = $this->getHeight();
        }

        return (int) $height;
    }

    /**
     * Resize image to new width and height
     *
     * @param boolean $proportions Set FALSE to resize without keeping proportions
     * @return boolean
     */
    public function resize($proportions = true)
    {
        if (!is_resource($this->image))
            return false;

        // Get the new image width and height
        $width = ($this->width) ? $this->width : $this->getWidth();
        $height = ($this->height) ? $this->height : $this->getHeight();

        // Adjust width or height for keeping image proportions
        if ($proportions) {
            $ratio_x = $width / $this->getWidth();
            $ratio_y = $height / $this->getHeight();

            if ($ratio_x <= $ratio_y) { $height = $this->getAutoHeight($width); }
            else { $width = $this->getAutoWidth($height); }
        }

        // Execute resizing
        $this->doResize($width, $height);
        return true;
    }

    /**
     * Resize image to a new width with keeping proportions
     *
     * @return boolean
     */
    public function resizeToWidth()
    {
        if (!is_resource($this->image) || !$this->width)
            return false;

        $height = $this->getAutoHeight($this->width);
        $this->doResize($this->width, $height);
        return true;
    }

    /**
     * Resize image to a new height with keeping proportions
     *
     * @return boolean
     */
    public function resizeToHeight()
    {
        if (!is_resource($this->image) || !$this->height)
            return false;

        $width = $this->getAutoWidth($this->height);
        $this->doResize($width, $this->height);
        return true;
    }

    /**
     * Resize image with keeping proportions and cropping
     *
     * @param string $position_x [optional] Horisontal cropping position
     * @param string $position_y [optional] Vertical cropping position
     * @return boolean
     */
    public function resizeToFill($position_x = 'center', $position_y = 'center')
    {
        if (!is_resource($this->image))
            return false;

        // Get canvas width and height
        $width = ($this->width) ? $this->width : $this->getWidth();
        $height = ($this->height) ? $this->height : $this->getHeight();

        // Resize to a side which will fill the canvas best
        if ($this->getAutoHeight($width) < $height) { $this->resizeToHeight($height); }
        else { $this->resizeToWidth($width); }

        // Crop canvas
        $this->doCrop($width, $height, $position_x, $position_y);
        return true;
    }

    /**
     * Crop image
     *
     * @param string|integer $position_x [optional] Horisontal position of cropping
     * @param string|integer $position_y [optional] Vertical position of cropping
     * @return boolean
     */
    public function crop($position_x = 'center', $position_y = 'center')
    {
        if (!is_resource($this->image))
            return false;

        // Get new image width and height
        $width = ($this->width) ? $this->width : $this->getWidth();
        $height = ($this->height) ? $this->height : $this->getHeight();

        // Execute cropping
        $this->doCrop($width, $height, $position_x, $position_y);
        return true;
    }

    /**
     * Final resize action
     *
     * @param integer $width Image width
     * @param integer $height Image height
     * @return void
     */
    protected function doResize($width, $height)
    {
        if (!is_resource($this->image))
            return;

        // Don't do anything if new image will be larger than original
        // and upscaling is not allowed
        if (!$this->upscale && ($width > $this->getWidth() || $height > $this->getHeight())) {
            return;
        }

        // Create new resized image
        $new_image = imagecreatetruecolor($width, $height);
        $this->preserveTransparency($new_image);
        imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        $this->image = $new_image;
    }

    /**
     * Final crop action
     *
     * @param integer $width Canvas width
     * @param integer $height Canvas height
     * @param string|integer $position_x [optional] Horisontal cropping position
     * @param string|integer $position_y [optional] Vertical cropping position
     * @return void
     */
    protected function doCrop($width, $height, $position_x = 'center', $position_y = 'center')
    {
        if (!is_resource($this->image))
            return;

        // Adjust new canvas size to be not greater than actual image size
        if ($width  > $this->getWidth())  { $width  = $this->getWidth();  }
        if ($height > $this->getHeight()) { $height = $this->getHeight(); }

        // Calculate horisontal cropping position
        switch (strtolower($position_x)) {
            case 'left':
                $start_x = 0;
                break;
            case 'right':
                $start_x = $this->getWidth() - $width;
                break;
            case 'center':
            case 'middle':
                $start_x = ($this->getWidth() / 2) - ($width / 2);
                break;
            default:
                $start_x = (is_numeric($position_x) && $position_x > 0) ? (int) $position_x : 0;
        }

        // Calculate vertical cropping position
        switch (strtolower($position_y)) {
            case 'top':
                $start_y = 0;
                break;
            case 'bottom':
                $start_y = $this->getHeight() - $height;
                break;
            case 'center':
            case 'middle':
                $start_y = ($this->getHeight() / 2) - ($height / 2);
                break;
            default:
                $start_y = (is_numeric($position_y) && $position_y > 0) ? (int) $position_y : 0;
        }

        // Adjust the positions so they should not be out of image size
        if ($start_x > $this->getWidth() - $width)   { $start_x = $this->getWidth() - $width;   }
        if ($start_y > $this->getHeight() - $height) { $start_y = $this->getHeight() - $height; }

        // Create new cropped image
        $new_image = imagecreatetruecolor($width, $height);
        $this->preserveTransparency($new_image);
        imagecopy($new_image, $this->image, 0, 0, $start_x, $start_y, $width, $height);
        $this->image = $new_image;
    }

    /**
     * Keep transparency if image supports it
     *
     * @param resource $image Image resource passed by reference
     * @return void
     */
    protected function preserveTransparency(&$image)
    {
        // Transparency for PNG images
        if ($this->getImageType() == IMAGETYPE_PNG) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        // Transparency for GIF images
        if ($this->getImageType() == IMAGETYPE_GIF) {
            $transparent_index = imagecolortransparent($this->image);
            if ($transparent_index >= 0 && $transparent_index < imagecolorstotal($this->image)) {
                $transparent_color = imagecolorsforindex($this->image, $transparent_index);
            }

            if (isset($transparent_color)) {
                $transparent_new_color = imagecolorallocate($image, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
                $transparent_new_index = imagecolortransparent($image, $transparent_new_color);
                imagefill($image, 0, 0, $transparent_new_index );  // fill the new image with the transparent color
            }
        }
    }

    /**
     * Save the image to a file
     *
     * @param string $filename Full path to destination file
     * @param string $type [optional] Image type (jpg|gif|png)
     * @return boolean
     */
    public function save($filename, $type = null)
    {
        if (!is_resource($this->image))
            return false;

        // Detect destination image type by $type or by file extension
        if (!empty($type))
            $type = strtolower($type);
        else
            $type = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Save image
        if ((!$type || $type == 'jpg' || $type == 'jpeg') && (imagetypes() & IMG_JPG))
            $result = imagejpeg($this->image, $filename, $this->quality_jpg);
        elseif ($type == 'gif' && (imagetypes() & IMG_GIF))
            $result = imagegif($this->image, $filename);
        elseif ($type == 'png' && (imagetypes() & IMG_PNG))
            $result = imagepng($this->image, $filename, $this->quality_png);
        else
            $result = false;

        return $result;
    }
}