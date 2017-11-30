<?php
/*
* Copyright (c) 2008 http://www.webmotionuk.com / http://www.webmotionuk.co.uk
* "PHP & Jquery image upload & crop"
* Date: 2008-11-21
* Ver 1.2
* Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND 
* ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED 
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
* IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
* INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, 
* PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS 
* INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
* STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF 
* THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*
*/

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Personal;
class HomeController extends Controller
{
    
    private $upload_dir; 				                // The directory for the images to be saved in
    private $upload_path;			                	// The path to where the image will be saved
    private $large_image_prefix = "resize_"; 			// The prefix name to large image
    private $thumb_image_prefix = "thumbnail_";			// The prefix name to the thumb image
    private $large_image_name;                          // New name of the large image (append the timestamp to the filename)
    private $thumb_image_name;                          // New name of the thumbnail image (append the timestamp to the filename)
    private $max_file = "3"; 							// Maximum file size in MB
    private $max_width = "500";							// Max width allowed for the large image
    private $thumb_width = "100";						// Width of thumbnail image
    private $thumb_height = "100";						// Height of thumbnail image
    // Only one of these image types should be allowed for upload
    private $allowed_image_types = array('image/pjpeg'=>"jpg",'image/jpeg'=>"jpg",'image/jpg'=>"jpg",'image/png'=>"png",'image/x-png'=>"png",'image/gif'=>"gif");
    private $allowed_image_ext; // do not change this
    private $image_ext = "";	// initialise variable, do not change this.
    private $large_photo_exists;
    private $thumb_photo_exists;
    private $large_image_location;
    private $thumb_image_location;
    private $error;
    private $current_large_image_width;
    private $current_large_image_height;
    
    public function index(Request $request, $id){
    
        $this->initial($id);

        return view('home')
                    ->with('current_large_image_width', $this->current_large_image_width)
                    ->with('current_large_image_height', $this->current_large_image_height)
                    ->with('thumb_photo_exists', $this->thumb_photo_exists)
                    ->with('thumb_height', $this->thumb_height)
                    ->with('thumb_width', $this->thumb_width)
                    ->with('upload_path', $this->upload_path)
                    ->with('large_image_name', $this->large_image_name)
                    ->with('large_photo_exists', $this->large_photo_exists)
                    ->with('error', $this->error)
                    ->with('upload_dir', $this->upload_dir)
                    ->with('large_image_location', $this->large_image_location)
                    ->with('id', $id);
    }

    public function upload(Request $request){

        $this->initial();

        //Get the file information
        $image = $request->file('image');
        $userfile_name = $image->getClientOriginalName();
        $userfile_tmp = $image->getRealPath();
        $userfile_size = $image->getSize();
        $userfile_type = $image->getMimeType();
        $filename = basename($image->getClientOriginalName());
        $file_ext = $image->getClientOriginalExtension();
        // $input['imagename'] = time().'.'.$image->getClientOriginalExtension();

        //Only process if the file is a JPG, PNG or GIF and below the allowed limit
        if((!empty($image)) && ($image->getError() == 0)) {
            
            foreach ($this->allowed_image_types as $mime_type => $ext) {
                //loop through the specified image types and if they match the extension then break out
                //everything is ok so go and check file size
                if($file_ext==$ext && $userfile_type==$mime_type){
                    $this->error = "";
                    break;
                }else{
                    $this->error = "Only <strong>".$this->image_ext."</strong> images accepted for upload<br />";
                }
            }
            //check if the file size is above the allowed limit
            if ($userfile_size > ($this->max_file*1048576)) {
                $this->error.= "Images must be under ".$this->max_file."MB in size";
            }
            
        }else{
            $this->error= "Select an image for upload";
        }
        //Everything is ok, so we can upload the image.
        if (strlen($this->error)==0){
            
            if (isset($image)){
                //this file could now has an unknown file extension (we hope it's one of the ones set above!)
                $this->large_image_location = $this->large_image_location . "." . $file_ext;
                $this->thumb_image_location = $this->thumb_image_location . "." . $file_ext;
        
                //put the file ext in the session so we know what file to look for once its uploaded
                session(['user_file_ext' => "." . $file_ext]);

                // move_uploaded_file($userfile_tmp, $large_image_location);
                // $image->move($this->large_image_location, $input['imagename']);
                move_uploaded_file($userfile_tmp, $this->large_image_location);
                chmod($this->large_image_location, 0777);
                
                $width = $this->getWidth($this->large_image_location);
                $height = $this->getHeight($this->large_image_location);
                //Scale the image if it is greater than the width set above
                if ($width > $this->max_width){
                    $scale = $this->max_width / $width;
                    $uploaded = $this->resizeImage($this->large_image_location, $width, $height, $scale);
                }else{
                    $scale = 1;
                    $uploaded = $this->resizeImage($this->large_image_location, $width, $height, $scale);
                }
                //Delete the thumbnail file so the user can create a new one
                if (file_exists($this->thumb_image_location)) {
                    unlink($this->thumb_image_location);
                }
            }
        }

        return \Redirect::route('home', ['id' => session('random_key')])
                                    ->with('current_large_image_width', $this->current_large_image_width)
                                    ->with('current_large_image_height', $this->current_large_image_height)
                                    ->with('thumb_photo_exists', $this->thumb_photo_exists)
                                    ->with('thumb_height', $this->thumb_height)
                                    ->with('thumb_width', $this->thumb_width)
                                    ->with('upload_path', $this->upload_path)
                                    ->with('large_image_name', $this->large_image_name)
                                    ->with('large_photo_exists', $this->large_photo_exists)
                                    ->with('error', $this->error)
                                    ->with('upload_dir', $this->upload_dir)
                                    ->with('large_image_location', $this->large_image_location)
                                    ->with('id', $request->input('id'));
    }

    public function upload_thumbnail(Request $request){
     $this->initial();

        if(strlen($this->large_photo_exists) > 0){
            //Get the new coordinates to crop the image.
	        $x1 = $request->input('x1');
            $y1 = $request->input('y1');
            $x2 = $request->input('x2');
            $y2 = $request->input('y2');
            $w = $request->input('w');
            $h = $request->input('h');
            //Scale the image to the thumb_width set above
            $scale = $this->thumb_width/$w;
            $cropped = $this->resizeThumbnailImage($this->thumb_image_location, $this->large_image_location, $w, $h, $x1, $y1, $scale);


            $personal = new Personal();
            $temp = file_get_contents($this->thumb_image_location);
            $blob = base64_encode($temp);
            $personal->file = $blob;
            $personal->save();

            return \Redirect::route('home', ['id' => session('random_key')])
                                        ->with('current_large_image_width', $this->current_large_image_width)
                                        ->with('current_large_image_height', $this->current_large_image_height)
                                        ->with('thumb_photo_exists', $this->thumb_photo_exists)
                                        ->with('thumb_height', $this->thumb_height)
                                        ->with('thumb_width', $this->thumb_width)
                                        ->with('upload_path', $this->upload_path)
                                        ->with('large_image_name', $this->large_image_name)
                                        ->with('large_photo_exists', $this->large_photo_exists)
                                        ->with('error', $this->error)
                                        ->with('upload_dir', $this->upload_dir)
                                        ->with('large_image_location', $this->large_image_location)
                                        ->with('id', $request->input('id'));
        }
      
        // echo public_path();
        // echo '$this->large_image_location : ' . $this->large_image_location;
        // die('$this->large_photo_exists : '.$this->large_photo_exists);

    }

    public function delete(Request $request){
        $this->initial();
        $this->large_image_location = $this->upload_path . $this->large_image_prefix . $request->input('t');
        $this->thumb_image_location = $this->upload_path . $this->thumb_image_prefix . $request->input('t');
        
        if (file_exists($this->large_image_location)) {
            unlink($this->large_image_location);
        }

        if (file_exists($this->thumb_image_location)) {
            unlink($this->thumb_image_location);
        }

        return \Redirect::route('home', ['id' => $request->input('id')])
                                ->with('current_large_image_width', $this->current_large_image_width)
                                ->with('current_large_image_height', $this->current_large_image_height)
                                ->with('thumb_photo_exists', $this->thumb_photo_exists)
                                ->with('thumb_height', $this->thumb_height)
                                ->with('thumb_width', $this->thumb_width)
                                ->with('upload_path', $this->upload_path)
                                ->with('large_image_name', $this->large_image_name)
                                ->with('large_photo_exists', $this->large_photo_exists)
                                ->with('error', $this->error)
                                ->with('upload_dir', $this->upload_dir)
                                ->with('large_image_location', $this->large_image_location)
                                ->with('id', $request->input('id'));
    }

    public function resizeImage($image, $width, $height, $scale) {
        list($imagewidth, $imageheight, $imageType) = getimagesize($image);
        $imageType = image_type_to_mime_type($imageType);
        $newImageWidth = ceil($width * $scale);
        $newImageHeight = ceil($height * $scale);
        $newImage = imagecreatetruecolor($newImageWidth,$newImageHeight);
        switch($imageType) {
            case "image/gif":
                $source=imagecreatefromgif($image); 
                break;
            case "image/pjpeg":
            case "image/jpeg":
            case "image/jpg":
                $source=imagecreatefromjpeg($image); 
                break;
            case "image/png":
            case "image/x-png":
                $source=imagecreatefrompng($image); 
                break;
          }
        imagecopyresampled($newImage,$source,0,0,0,0,$newImageWidth,$newImageHeight,$width,$height);
        
        switch($imageType) {
            case "image/gif":
                  imagegif($newImage,$image); 
                break;
              case "image/pjpeg":
            case "image/jpeg":
            case "image/jpg":
                  imagejpeg($newImage,$image,90); 
                break;
            case "image/png":
            case "image/x-png":
                imagepng($newImage,$image);  
                break;
        }
        
        chmod($image, 0777);
        return $image;
    }

    //You do not need to alter these functions
    public function resizeThumbnailImage($thumb_image_name, $image, $width, $height, $start_width, $start_height, $scale){
        list($imagewidth, $imageheight, $imageType) = getimagesize($image);
        $imageType = image_type_to_mime_type($imageType);
        
        $newImageWidth = ceil($width * $scale);
        $newImageHeight = ceil($height * $scale);
        $newImage = imagecreatetruecolor($newImageWidth,$newImageHeight);
        switch($imageType) {
            case "image/gif":
                $source=imagecreatefromgif($image); 
                break;
            case "image/pjpeg":
            case "image/jpeg":
            case "image/jpg":
                $source=imagecreatefromjpeg($image); 
                break;
            case "image/png":
            case "image/x-png":
                $source=imagecreatefrompng($image); 
                break;
        }
        imagecopyresampled($newImage,$source,0,0,$start_width,$start_height,$newImageWidth,$newImageHeight,$width,$height);
        switch($imageType) {
            case "image/gif":
                imagegif($newImage,$thumb_image_name); 
                break;
            case "image/pjpeg":
            case "image/jpeg":
            case "image/jpg":
                imagejpeg($newImage,$thumb_image_name,90); 
                break;
            case "image/png":
            case "image/x-png":
                imagepng($newImage,$thumb_image_name);  
                break;
        }
        chmod($thumb_image_name, 0777);
        return $thumb_image_name;
    }

    //You do not need to alter these functions
    public function getHeight($image) {
        $size = getimagesize($image);
        $height = $size[1];
        return $height;
    }
    //You do not need to alter these functions
    public function getWidth($image) {
        $size = getimagesize($image);
        $width = $size[0];
        return $width;
    }

    public function initial($id = ''){
        // session()->flush();
        if(!session()->has('random_key') || strlen(session('random_key')) == 0){
            session(['random_key' => $id]); //strtotime(date('Y-m-d H:i:s'))
            session(['user_file_ext' => '']);
            session()->save();
        }
    
        $this->upload_dir = 'upload_pic';
        $this->upload_path = $this->upload_dir . '/';
        $this->large_image_name = $this->large_image_prefix . session('random_key');
        $this->thumb_image_name = $this->thumb_image_prefix . session('random_key');
        $this->allowed_image_ext = array_unique($this->allowed_image_types);

        foreach ($this->allowed_image_ext as $mime_type => $ext) {
            $this->image_ext.= strtoupper($ext)." ";
        }

        //Image Locations
        $this->large_image_location = $this->upload_path . $this->large_image_name . session('user_file_ext');
        $this->thumb_image_location = $this->upload_path . $this->thumb_image_name . session('user_file_ext');

        //Create the upload directory with the right permissions if it doesn't exist
        if(!is_dir($this->upload_dir)){
            mkdir($this->upload_dir, 0777);
            chmod($this->upload_dir, 0777);
        }
        //Check to see if any images with the same name already exist
        // die($this->large_image_location.'55');
        if (file_exists($this->large_image_location)){
            if(file_exists($this->thumb_image_location)){
                $this->thumb_photo_exists = "<img src=\"". \URL::to('/') ."/". $this->upload_path . $this->thumb_image_name . session('user_file_ext') . "\" alt=\"Thumbnail Image\"/>";
            }else{
                $this->thumb_photo_exists = "";
            }
            $this->large_photo_exists = "<img src=\"". \URL::to('/') . "/" . $this->upload_path . $this->large_image_name . session('user_file_ext') . "\" alt=\"Large Image\"/>";
        } else {
            $this->large_photo_exists = "";
            $this->thumb_photo_exists = "";
        }

        if(strlen($this->large_photo_exists) > 0){
            $this->current_large_image_width = $this->getWidth($this->large_image_location);
            $this->current_large_image_height = $this->getHeight($this->large_image_location);    
        }

    }

    public function __construct(Request $request){
        
    }
}
