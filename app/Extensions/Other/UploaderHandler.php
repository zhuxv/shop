<?php
/**
 * Uploader Package
 * User: zng
 * Date: 2020/7/6
 * Time: 13:49
 */
namespace App\Extensions\Other;

use App\Exceptions\OrdinaryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

Class UploaderHandler {

    /**
     * 本地图片上传服务
     * @param UploadedFile $file 文件对象
     * @param string $fileType 文件类型,参照配置文件中的类型
     * @throws OrdinaryException
     * @return string
     */
    public function upload_local( UploadedFile $file, string $fileType='image' )
    {
        $config = config('filesystems');
        // 检查文件的合法性
        if ( ! $file->isValid() || ! in_array($file->extension(), $config['ext'][$fileType]) ) {
            throw new OrdinaryException('文件不合法');
        }
        return $file->store($fileType, 'public');
    }

    /**
     * 删除本地图片服务
     * @param string|array $files 文件名
     * @return bool
     */
    public function delete_local( $files )
    {
        if ( ! $files ) return false;
        return Storage::disk('public')->delete($files);
    }

}